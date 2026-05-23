<?php

use Devkit\Ui\Trail\TrailManager;

if (!function_exists('trail')) {
    function trail($namespace = 'default')
    {
        return TrailManager::register($namespace);
    }
}
