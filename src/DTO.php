<?php

namespace Atwinta\DTO;

use Atwinta\DTO\Exceptions\JsonException;
use Atwinta\DTO\Exceptions\MissingRequiredValueException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class DTO implements Arrayable, Jsonable, \JsonSerializable
{
    /**
     * @param  array<string, mixed>|object  $source
     * @param  bool  $clone_objects
     * @return static
     *
     * @throws MissingRequiredValueException
     */
    public static function create(array|object $source, bool $clone_objects = true): static
    {
        $source = static::sourceToArray($source);

        $reflection = static::reflection();
        $constructor = $reflection->getConstructor();
        if (is_null($constructor)) {
            return new static();
        }

        $dependencies = $constructor->getParameters();

        $parameters = [];
        foreach ($dependencies as $dependency) {
            $name = $dependency->name;
            switch (true) {
                case array_key_exists($name, $source):
                    $parameters[] = self::makeDependencyValue(
                        $dependency,
                        $clone_objects && is_object($source[$name]) ?
                            clone $source[$name] : $source[$name],
                    );
                    unset($source[$name]);
                    break;
                case $dependency->isDefaultValueAvailable():
                    /** @throws never */
                    $parameters[] = $dependency->getDefaultValue();
                    break;
                case $dependency->isVariadic():
                    $parameters = array_merge($parameters, array_values($source));
                    break;
                case $dependency->allowsNull():
                    $parameters[] = null;
                    break;
                default:
                    throw new MissingRequiredValueException(
                        "Параметр $name должен быть указан при инстанциировании ".static::class
                    );
            }
        }

        return $reflection->newInstanceArgs($parameters);
    }

    /**
     * @param  \ReflectionParameter  $dependency
     * @param  mixed  $value
     * @return mixed
     */
    public static function makeDependencyValue(\ReflectionParameter $dependency, mixed $value): mixed
    {
        /** @var \ReflectionNamedType|null $type */
        $type = $dependency->getType();
        if ($type === null || $type->isBuiltin()) {
            return $value;
        }
        switch (true) {
            case is_subclass_of($type->getName(), self::class):
                /** @var callable(): mixed $callback */
                $callback = $type->getName().'::create';

                return call_user_func($callback, $value);
            case ($type->getName() === Collection::class || is_subclass_of($type->getName(), Collection::class))
            && is_iterable($value):

                /** @var iterable<int|string, mixed> $value */
                return Collection::make($value);
            case is_subclass_of($type->getName(), \UnitEnum::class):
                /** @var callable(): mixed $callback */
                $callback = $type->getName().'::from';

                return call_user_func($callback, $value);
            default:
                return $value;
        }
    }

    /**
     * @param  array<string, mixed>|object  $source
     * @param  bool  $clone_objects
     * @return $this
     */
    public function fill(array|object $source, bool $clone_objects = true): self
    {
        $source = static::sourceToArray($source);

        $properties = static::properties();

        foreach ($properties as $property) {
            $name = $property->name;
            $this->$name = $clone_objects && is_object($source[$name]) ?
                clone $source[$name] : $source[$name];
        }

        return $this;
    }

    public function toArray(): array
    {
        $properties = static::properties();
        $array = [];
        foreach ($properties as $property) {
            if (! $property->isInitialized($this)) {
                continue;
            }

            $name = $property->name;
            $value = $this->$name;

            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            $array[$name] = $value;
        }

        return $array;
    }

    /**
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        $json = json_encode($this, $options);
        if ($json === false) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param  array<string, mixed>|object  $source
     * @return array<string, mixed>
     */
    protected static function sourceToArray(array|object $source): array
    {
        return match (true) {
            // Каст в array нужен, потому что PHPStan не знает, что в данном случае
            // validated 100% вернёт массив
            $source instanceof FormRequest => (array) $source->validated(),
            $source instanceof Arrayable => $source->toArray(),
            ! is_array($source) => (array) $source,
            default => $source
        };
    }

    /**
     * @return array<\ReflectionProperty>
     */
    final protected static function properties(): array
    {
        return static::reflection()->getProperties(\ReflectionProperty::IS_PUBLIC);
    }

    /**
     * @return \ReflectionClass<static>
     */
    final protected static function reflection(): \ReflectionClass
    {
        /** @throws never */
        return new \ReflectionClass(static::class);
    }
}
