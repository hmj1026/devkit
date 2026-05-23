<?php

namespace Devkit\Tests\Laravel\Integration;

use Devkit\Laravel\DevkitServiceProvider;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Contracts\Console\Kernel;

class DevkitGeneratorsEnabledTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array(DevkitServiceProvider::class);
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('devkit.commands.generators.enabled', true);
    }

    public function testExactlyFiveDevkitGeneratorsAreRegisteredWhenEnabled()
    {
        $commands = array_filter(array_keys($this->app->make(Kernel::class)->all()), function ($name) {
            return strpos($name, 'devkit:make:') === 0;
        });
        sort($commands);

        $this->assertSame(array(
            'devkit:make:action',
            'devkit:make:audit-log-target',
            'devkit:make:enum',
            'devkit:make:http-client',
            'devkit:make:service',
        ), array_values($commands));
    }
}
