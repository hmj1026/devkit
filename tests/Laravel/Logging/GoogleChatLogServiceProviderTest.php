<?php

namespace Devkit\Tests\Laravel\Logging;

use Devkit\Laravel\Logging\GoogleChat\GoogleChatLogServiceProvider;
use Devkit\Logging\GoogleChat\GoogleChatLogHandler;
use Devkit\Tests\Laravel\TestCase;

class GoogleChatLogServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array(GoogleChatLogServiceProvider::class);
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('logging.channels.googlechat', array(
            'driver' => 'googlechat',
            'url' => 'https://chat.example.test/webhook',
            'app_name' => 'Devkit',
            'env' => 'testing',
        ));
    }

    public function testRegistersGoogleChatLogDriver()
    {
        $logger = $this->app['log']->channel('googlechat');
        $handlers = $logger->getHandlers();

        $this->assertInstanceOf(GoogleChatLogHandler::class, $handlers[0]);
    }
}
