<?php

namespace Devkit\Laravel;

use Devkit\Laravel\Command\CommandServiceProvider;
use Devkit\Laravel\Database\DatabaseServiceProvider;
use Devkit\Laravel\Http\HttpServiceProvider;
use Devkit\Laravel\Logging\GoogleChat\GoogleChatLogServiceProvider;
use Devkit\Laravel\Messaging\MessagingServiceProvider;
use Devkit\Laravel\Queue\SqsFifo\SqsFifoServiceProvider;
use Devkit\Laravel\Search\SearchServiceProvider;
use Devkit\Laravel\Storage\StorageServiceProvider;
use Devkit\Laravel\Ui\MetaTag\MetaTagServiceProvider;
use Devkit\Laravel\Ui\Trail\TrailServiceProvider;
use Illuminate\Support\ServiceProvider;

class DevkitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/devkit.php', 'devkit');
    }

    public function boot()
    {
        $this->registerModuleProviders();

        $this->publishes(array(
            __DIR__ . '/../../config/devkit.php' => config_path('devkit.php'),
        ), 'devkit-config');

        $this->publishes(array(
            __DIR__ . '/../../stubs/devkit' => base_path('stubs/vendor/devkit'),
        ), 'devkit-stubs');
    }

    protected function registerModuleProviders()
    {
        foreach ($this->moduleProviders() as $module => $providers) {
            if (!$this->moduleEnabled($module)) {
                continue;
            }

            foreach ($providers as $provider) {
                $this->app->register($provider);
            }
        }
    }

    protected function moduleProviders()
    {
        return array(
            'logging' => array(GoogleChatLogServiceProvider::class),
            'http' => array(HttpServiceProvider::class),
            'storage' => array(StorageServiceProvider::class),
            'search' => array(SearchServiceProvider::class),
            'database' => array(DatabaseServiceProvider::class),
            'messaging' => array(MessagingServiceProvider::class),
            'ui' => array(TrailServiceProvider::class, MetaTagServiceProvider::class),
            'queue' => array(SqsFifoServiceProvider::class),
            'commands' => array(CommandServiceProvider::class),
        );
    }

    protected function moduleEnabled($module)
    {
        return (bool) $this->app['config']->get('devkit.modules.' . $module . '.enabled', true);
    }
}
