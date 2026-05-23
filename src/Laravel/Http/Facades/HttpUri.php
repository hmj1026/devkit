<?php

namespace Devkit\Laravel\Http\Facades;

use Illuminate\Support\Facades\Facade;

class HttpUri extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'devkit.http_uri';
    }
}
