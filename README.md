# Data Transfer Object Package

## Установка

Установка через Composer:

```shell
composer require atwinta/data-transfer-object
```

## Использование

### Создание DTO

`php artisan make:dto <name>` - создаст класс в папке `app/DTO`

После создания, в DTO нужно будет указать поля. Рекомендуется указывать их в конструкторе,
потому что поля, указанные в конструкторе можно заполнить используя метод `DTO::create()` и
сделать обязательными для создания DTO.

Для уменьшения количества кода рекомендуется определять поля в конструкторе:

```php
class User extends \Atwinta\DTO\DTO
{
    public function __construct(
        public int $id
    ) {}
}
```

### Использование DTO

Для создания DTO можно напрямую использовать конструктор или использовать метод
`DTO::create()`. Этот метод принимает массив вида `[название поля => значение]`
и самостоятельно вызывает конструктор, передавая значения в нужном порядке.
Если передать в него объект, он будет приведён к массиву следующим образом:

* Если объект наследуется от `\Illuminate\Foundation\Http\FormRequest`, на нём
  будет вызван метод `validated()`
* Если объект имплементирует интерфейс `\Illuminate\Contracts\Support\Arrayable`,
  на нём будет вызван метод `toArray()`
* Все остальные объекты будут приведены к массиву используя cast `(array) $object`

Так как базовый класс DTO имплементирует интерфейс `\Illuminate\Contracts\Support\Arrayable`,
DTO можно заполнить из других DTO никак не изменяя их перед передачей в `DTO::create()`.

Для массового заполнения уже созданного DTO можно использовать метод `DTO::fill()`.
Он принимает те же параметры, что и `DTO::create()`, и обрабатывает их тем же образом.

По умолчанию, `DTO::create()` и `DTO::fill()` клонируют объекты перед записью в
поля заполняемого DTO. Чтобы отключить клонирование, нужно передать `false` во
второй параметр этих методов.
