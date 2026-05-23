<?php

namespace Devkit\Laravel\Ui\Trail;

use Devkit\Ui\Trail\TrailManager;
use Illuminate\Support\ServiceProvider;

class TrailServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('devkit.trail', function () {
            return new TrailManager();
        });
    }
}
