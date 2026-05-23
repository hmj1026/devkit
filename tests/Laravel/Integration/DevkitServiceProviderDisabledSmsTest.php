<?php

namespace Devkit\Tests\Laravel\Integration;

use Devkit\Laravel\DevkitServiceProvider;
use Devkit\Tests\Laravel\TestCase;

class DevkitServiceProviderDisabledSmsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array(DevkitServiceProvider::class);
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('devkit.modules.messaging.enabled', false);
    }

    public function testDisabledMessagingModuleSkipsSmsBinding()
    {
        $this->assertFalse($this->app->bound('devkit.sms'));
    }
}
