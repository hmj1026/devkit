<?php

namespace Devkit\Laravel\Http;

use Devkit\Laravel\Http\Asset\HttpUriCacheAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('devkit.http_uri', function ($app) {
            $config = $app['config']->get('devkit.http.asset_version', array());

            return new HttpUriCacheAdapter(
                Cache::store(),
                null,
                isset($config['cache_key']) ? $config['cache_key'] : 'devkit.asset_version',
                isset($config['ttl']) ? $config['ttl'] : 3600
            );
        });
    }
}
