<?php

namespace Devkit\Laravel\Storage\Facades;

use Illuminate\Support\Facades\Facade;

class FileUploader extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'devkit.file_uploader';
    }
}
