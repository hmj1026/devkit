<?php

namespace Devkit\Laravel\Search\Facades;

use Illuminate\Support\Facades\Facade;

class Elasticsearch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'devkit.elasticsearch';
    }
}
