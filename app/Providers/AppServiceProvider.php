<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Provides the functionality to register services and commands.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment("production"))
        {
            collect(Artisan::all())->each(function (Command $command)
            {
                if ($command instanceof TestCommand)
                {
                    $command->setHidden();
                }
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
