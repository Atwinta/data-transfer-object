<?php

namespace Atwinta\DTO\Commands;

use Illuminate\Console\GeneratorCommand;

class DTOMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:dto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new DTO class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'DTO';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/dto.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return "$rootNamespace\\DTO";
    }
}
