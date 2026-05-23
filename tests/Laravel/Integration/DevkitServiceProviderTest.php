<?php

namespace Devkit\Tests\Laravel\Integration;

use Devkit\Laravel\DevkitServiceProvider;
use Devkit\Messaging\Sms\SmsManager;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Contracts\Console\Kernel;

class DevkitServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array(DevkitServiceProvider::class);
    }

    public function testDefaultModulesRegisterUserFacingBindings()
    {
        $this->assertTrue($this->app->bound('devkit.sms'));
        $this->assertTrue($this->app->bound('devkit.http_uri'));
        $this->assertTrue($this->app->bound('devkit.file_uploader'));
        $this->assertTrue($this->app->bound('devkit.elasticsearch'));
        $this->assertInstanceOf(SmsManager::class, $this->app->make('devkit.sms'));
    }

    public function testGeneratorsAreHiddenByDefault()
    {
        $commands = $this->app->make(Kernel::class)->all();

        $this->assertArrayNotHasKey('devkit:make:service', $commands);
        $this->assertArrayHasKey('devkit:install', $commands);
    }
}
