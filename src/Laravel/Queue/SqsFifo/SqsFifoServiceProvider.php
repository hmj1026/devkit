<?php

namespace Devkit\Laravel\Queue\SqsFifo;

use Illuminate\Support\ServiceProvider;

class SqsFifoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app['queue']->addConnector('sqs-fifo', function () {
            return new SqsFifoConnector();
        });
    }
}
