<?php

namespace Devkit\Laravel\Ui\Facades;

use Illuminate\Support\Facades\Facade;

class MetaTags extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'devkit.meta';
    }
}
