<?php

namespace Devkit\Tests\Logging\GoogleChat;

use Devkit\Logging\GoogleChat\Exception\GoogleChatLogWebHookUrlNotSettingException;
use Devkit\Logging\GoogleChat\GoogleChatLogHandler;
use Devkit\Logging\GoogleChat\GoogleChatLogHandlerFactory;
use Devkit\Tests\Logging\GoogleChat\Fixture\RecordingHttpClient;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class GoogleChatLogHandlerTest extends TestCase
{
    public function testWebhookUrlMissingThrowsException()
    {
        $this->expectException(GoogleChatLogWebHookUrlNotSettingException::class);

        new GoogleChatLogHandler('', 'myapp', 'test');
    }

    public function testHandlerExtendsAbstractProcessingHandler()
    {
        $handler = new GoogleChatLogHandler(
            'https://chat.example.com/webhook',
            'myapp',
            'test',
            array(),
            new RecordingHttpClient()
        );

        $this->assertInstanceOf(AbstractProcessingHandler::class, $handler);
    }

    /**
     * Polyfill branch coverage (Monolog 3 cell). The dispatcher in
     * GoogleChatLogHandler.php aliases the canonical name to the M3 concrete
     * when Monolog\LogRecord exists. Skipped on Monolog 2 cells, where the M2
     * branch is exercised by the sibling test below. Together the two prove
     * both branches across the CI matrix (neither runs in a single process).
     */
    public function testDispatcherResolvesToMonolog3Concrete()
    {
        if (!class_exists('Monolog\\LogRecord')) {
            $this->markTestSkipped('Monolog 3 not installed; the M2 branch is active on this cell.');
        }

        $handler = new GoogleChatLogHandler(
            'https://chat.example.com/webhook',
            'myapp',
            'test',
            array(),
            new RecordingHttpClient()
        );

        $this->assertInstanceOf(
            'Devkit\\Logging\\GoogleChat\\Internal\\GoogleChatLogHandlerM3',
            $handler
        );
    }

    /**
     * Polyfill branch coverage (Monolog 2 cell). Mirror of the M3 test above.
     */
    public function testDispatcherResolvesToMonolog2Concrete()
    {
        if (class_exists('Monolog\\LogRecord')) {
            $this->markTestSkipped('Monolog 3 installed; the M3 branch is active on this cell.');
        }

        $handler = new GoogleChatLogHandler(
            'https://chat.example.com/webhook',
            'myapp',
            'test',
            array(),
            new RecordingHttpClient()
        );

        $this->assertInstanceOf(
            'Devkit\\Logging\\GoogleChat\\Internal\\GoogleChatLogHandlerM2',
            $handler
        );
    }

    public function testErrorLevelDispatchesRedColoredCard()
    {
        $client = new RecordingHttpClient();
        $logger = $this->makeLogger($client);

        $logger->error('db connection lost');

        $this->assertCount(1, $client->requests);
        $request = $client->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(
            'https://chat.example.com/webhook',
            (string) $request->getUri()
        );

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertNotNull($payload, 'webhook body must decode as JSON');

        // Walk into the card's text widget and verify the red colour band.
        $widgetText = $payload['cardsV2'][0]['card']['sections'][0]['widgets'][0]['textParagraph']['text'];
        $this->assertStringContainsString('#d32f2f', $widgetText, 'error level must render with red hex');
        $this->assertStringContainsString('db connection lost', $widgetText);
    }

    public function testWarningLevelUsesYellow()
    {
        $client = new RecordingHttpClient();
        $logger = $this->makeLogger($client);

        $logger->warning('disk filling');

        $widgetText = $this->extractWidgetText($client->requests[0]);
        $this->assertStringContainsString('#fbc02d', $widgetText);
    }

    public function testInfoLevelUsesGreen()
    {
        $client = new RecordingHttpClient();
        $logger = $this->makeLogger($client);

        $logger->info('user signed in');

        $widgetText = $this->extractWidgetText($client->requests[0]);
        $this->assertStringContainsString('#388e3c', $widgetText);
    }

    public function testDebugLevelUsesBlack()
    {
        $client = new RecordingHttpClient();
        $logger = $this->makeLogger($client);

        $logger->debug('trace fired');

        $widgetText = $this->extractWidgetText($client->requests[0]);
        $this->assertStringContainsString('#212121', $widgetText);
    }

    public function testMentionOnErrorRendersUsersLiteral()
    {
        $client = new RecordingHttpClient();
        $handler = new GoogleChatLogHandler(
            'https://chat.example.com/webhook',
            'myapp',
            'prod',
            array('error' => 'users/12345'),
            $client
        );
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $logger->error('payment failed');

        $payload = json_decode((string) $client->requests[0]->getBody(), true);
        $this->assertStringContainsString('<users/12345>', $payload['text']);
    }

    public function testAtAllBroadcastForCritical()
    {
        $client = new RecordingHttpClient();
        $handler = new GoogleChatLogHandler(
            'https://chat.example.com/webhook',
            'myapp',
            'prod',
            array('critical' => '@all'),
            $client
        );
        $logger = new Logger('test');
        $logger->pushHandler($handler);

        $logger->critical('database down');

        $payload = json_decode((string) $client->requests[0]->getBody(), true);
        $this->assertStringContainsString('<users/all>', $payload['text']);
    }

    public function testHeaderCarriesLevelAppEnvAndTimestamp()
    {
        $client = new RecordingHttpClient();
        $logger = $this->makeLogger($client, 'orders-api', 'staging');

        $logger->error('boom');

        $payload = json_decode((string) $client->requests[0]->getBody(), true);
        $header = $payload['cardsV2'][0]['card']['header'];
        $this->assertSame('ERROR — orders-api@staging', $header['title']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $header['subtitle']
        );
    }

    public function testFactoryAssemblesHandlerFromConfig()
    {
        $client = new RecordingHttpClient();
        $handler = GoogleChatLogHandlerFactory::create(array(
            'url' => 'https://chat.example.com/webhook',
            'app_name' => 'myapp',
            'env' => 'qa',
            'mentions' => array('error' => 'users/99'),
            'http_client' => $client,
        ));

        $logger = new Logger('test');
        $logger->pushHandler($handler);
        $logger->error('via factory');

        $this->assertCount(1, $client->requests);
        $payload = json_decode((string) $client->requests[0]->getBody(), true);
        $this->assertStringContainsString('<users/99>', $payload['text']);
        $this->assertStringContainsString('myapp@qa', $payload['cardsV2'][0]['card']['header']['title']);
    }

    public function testFactoryRejectsMissingUrl()
    {
        $this->expectException(GoogleChatLogWebHookUrlNotSettingException::class);
        GoogleChatLogHandlerFactory::create(array('app_name' => 'x', 'env' => 'y'));
    }

    /**
     * @param  RecordingHttpClient  $client
     * @param  string  $appName
     * @param  string  $env
     * @return Logger
     */
    private function makeLogger(RecordingHttpClient $client, $appName = 'myapp', $env = 'prod')
    {
        $handler = new GoogleChatLogHandler(
            'https://chat.example.com/webhook',
            $appName,
            $env,
            array(),
            $client
        );
        $logger = new Logger('test');
        $logger->pushHandler($handler);
        return $logger;
    }

    /**
     * Walk the cardsV2 path to the body text widget.
     */
    private function extractWidgetText($request)
    {
        $payload = json_decode((string) $request->getBody(), true);
        return $payload['cardsV2'][0]['card']['sections'][0]['widgets'][0]['textParagraph']['text'];
    }
}
