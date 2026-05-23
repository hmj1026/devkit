<?php

namespace Devkit\Laravel\Storage;

use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('devkit.file_uploader', function () {
            return new StorageAdapter();
        });
    }
}
