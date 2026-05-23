<?php

namespace Devkit\Laravel\Database;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Criteria::registerBuilderMacro();
    }
}
