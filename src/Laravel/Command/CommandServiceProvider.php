<?php

namespace Devkit\Laravel\Command;

use Devkit\Laravel\Command\Generators\MakeActionCommand;
use Devkit\Laravel\Command\Generators\MakeAuditLogTargetCommand;
use Devkit\Laravel\Command\Generators\MakeEnumCommand;
use Devkit\Laravel\Command\Generators\MakeHttpClientCommand;
use Devkit\Laravel\Command\Generators\MakeServiceCommand;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $commands = array(InstallCommand::class);

        if ($this->app['config']->get('devkit.commands.generators.enabled', false)) {
            $commands = array_merge($commands, array(
                MakeServiceCommand::class,
                MakeActionCommand::class,
                MakeEnumCommand::class,
                MakeAuditLogTargetCommand::class,
                MakeHttpClientCommand::class,
            ));
        }

        $this->commands($commands);
    }
}
