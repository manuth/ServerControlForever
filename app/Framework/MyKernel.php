<?php

namespace App\Framework;

use Illuminate\Console\Application;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command as LaravelZeroCommand;
use LaravelZero\Framework\Kernel;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;
use ReflectionClass;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

{
    /**
     * Provides the functionality to handle the execution of a command line interface.
     */
    class MyKernel extends Kernel
    {
        /**
         * {@inheritdoc}
         */
        public function getArtisan(): Application
        {
            if (is_null($this->artisan))
            {
                $artisan = new Application($this->app, $this->events, $this->app->version());
                $artisan->resolveCommands($this->commands);
                $commandsProperty = (new ReflectionClass(SymfonyApplication::class))->getProperty('commands');
                $commandMapProperty = (new ReflectionClass(Application::class))->getProperty('commandMap');
                /** @var Collection<string, string> */
                $commandMap = collect($commandMapProperty->getValue($artisan));
                /** @var string[] */
                $commands = $commandsProperty->getValue($artisan);

                $toRemove = collect($commandMap)->filter(function (string $commandClass)
                {
                    return ((config('app.env') == 'production') && ($commandClass == TestCommand::class)) ||
                        in_array($commandClass, config('commands.remove'));
                });

                $availableCommands = $commandMap->diff($toRemove);
                $commandMapProperty->setValue($artisan, $availableCommands->toArray());
                $artisan->setContainerCommandLoader();

                collect($artisan->all())->each(function (Command $command) use ($commands)
                {
                    if (in_array($command::class, config('commands.hidden', []), true))
                    {
                        $command->setHidden(true);
                    }

                    if (
                        $command instanceof LaravelZeroCommand &&
                        !in_array($command::class, $commands)
                    )
                    {
                        $this->app->call([$command, 'schedule']);
                    }
                });

                if (config("app.env") == "production")
                {
                    $artisan->getDefinition()->setOptions(
                        collect($artisan->getDefinition()->getOptions())->filter(function (InputOption $option)
                        {
                            return $option->getName() != "env";
                        })->toArray()
                    );
                }

                $this->artisan = $artisan;
            }

            return parent::getArtisan();
        }
    }
}
