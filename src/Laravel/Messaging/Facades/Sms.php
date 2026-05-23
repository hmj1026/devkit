<?php

namespace Devkit\Laravel\Messaging\Facades;

use Illuminate\Support\Facades\Facade;

class Sms extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'devkit.sms';
    }
}
