<?php

namespace Devkit\Tests\Laravel\Queue;

require_once __DIR__ . '/Fixture/FakeSqsClient.php';

use Aws\Sqs\SqsClient;
use BadMethodCallException;
use Devkit\Laravel\Queue\SqsFifo\Deduplicator\Content;
use Devkit\Laravel\Queue\SqsFifo\Deduplicator\Sqs;
use Devkit\Laravel\Queue\SqsFifo\Deduplicator\Unique;
use Devkit\Laravel\Queue\SqsFifo\SqsFifoConnector;
use Devkit\Laravel\Queue\SqsFifo\SqsFifoQueue;
use Devkit\Laravel\Queue\SqsFifo\SqsFifoQueueable;
use Devkit\Tests\Laravel\TestCase;

class SqsFifoQueueTest extends TestCase
{
    public function testPushRawAddsGroupAndContentDeduplication()
    {
        $client = new SqsClient();
        $queue = new SqsFifoQueue($client, 'orders.fifo', 'https://sqs.test', '', false, array(
            'group' => 'orders',
            'deduplicator' => new Content(),
        ));

        $id = $queue->pushRaw('{"hello":"world"}');

        $this->assertSame('msg-1', $id);
        $this->assertSame('orders', $client->messages[0]['MessageGroupId']);
        $this->assertSame(hash('sha256', '{"hello":"world"}'), $client->messages[0]['MessageDeduplicationId']);
    }

    public function testSqsDeduplicatorDefersToAwsContentBasedDeduplication()
    {
        $client = new SqsClient();
        $queue = new SqsFifoQueue($client, 'orders.fifo', 'https://sqs.test', '', false, array(
            'group' => 'orders',
            'deduplicator' => new Sqs(),
        ));

        $queue->pushRaw('payload');

        $this->assertArrayNotHasKey('MessageDeduplicationId', $client->messages[0]);
    }

    public function testUniqueDeduplicatorReturnsDifferentUuidValues()
    {
        $first = (new Unique())->deduplicate('payload');
        $second = (new Unique())->deduplicate('payload');

        $this->assertNotSame($first, $second);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/i', $first);
    }

    public function testLaterRejectsDelayByDefault()
    {
        $queue = new SqsFifoQueue(new SqsClient(), 'orders.fifo');

        $this->expectException(BadMethodCallException::class);
        $queue->later(60, 'job');
    }

    public function testConnectorRejectsNonFifoQueue()
    {
        $connector = new SqsFifoConnector();

        $this->expectException(\InvalidArgumentException::class);
        $connector->connect(array('queue' => 'orders'));
    }

    public function testQueueableTraitStoresMessageGroup()
    {
        $job = new class {
            use SqsFifoQueueable;
        };

        $this->assertSame($job, $job->onMessageGroup('tenant-42'));
        $this->assertSame('tenant-42', $job->messageGroup());
    }
}
