<?php

namespace Devkit\Laravel\Search;

use Devkit\Search\Client\ConnectionFactory;
use Devkit\Search\Client\ElasticsearchManager;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('devkit.elasticsearch', function ($app) {
            $default = $app['config']->get('devkit.search.default', 'default');
            $connections = $app['config']->get('devkit.search.connections', array());
            $factory = new ConnectionFactory();
            $factories = array();

            foreach ($connections as $name => $config) {
                $factories[$name] = function () use ($factory, $config) {
                    return $factory->make($config);
                };
            }

            return new ElasticsearchManager($default, $factories);
        });
    }
}
