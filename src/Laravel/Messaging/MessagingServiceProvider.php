<?php

namespace Devkit\Laravel\Messaging;

use Devkit\Messaging\Sms\SmsManager;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('devkit.sms', function ($app) {
            $manager = new SmsManager($app['config']->get('devkit.sms.default', 'null'));

            foreach ($app['config']->get('devkit.sms.drivers', array()) as $name => $factory) {
                if ($factory instanceof \Closure) {
                    $manager->extend($name, $factory);
                }
            }

            return $manager;
        });

        $this->app->alias('devkit.sms', SmsManager::class);
    }
}
