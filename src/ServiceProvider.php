<?php

namespace Atwinta\DTO;

use Atwinta\DTO\Commands\DTOMakeCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DTOMakeCommand::class,
            ]);
        }
    }
}
