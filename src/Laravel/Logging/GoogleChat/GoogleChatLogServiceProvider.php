<?php

namespace Devkit\Laravel\Logging\GoogleChat;

use Devkit\Logging\GoogleChat\GoogleChatLogHandlerFactory;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;

class GoogleChatLogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app['log']->extend('googlechat', function ($app, array $config) {
            $handler = GoogleChatLogHandlerFactory::create($config);
            $logger = new Logger(isset($config['name']) ? $config['name'] : 'googlechat');
            $logger->pushHandler($handler);

            return $logger;
        });
    }
}
