<?php

namespace Devkit\Laravel\Queue\SqsFifo;

use Aws\Sqs\SqsClient;
use BadMethodCallException;
use Devkit\Laravel\Queue\SqsFifo\Contract\Deduplicator;
use Devkit\Laravel\Queue\SqsFifo\Deduplicator\Content;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Log;

class SqsFifoQueue extends SqsQueue
{
    /**
     * @var array
     */
    protected $fifoConfig;

    public function __construct(
        SqsClient $sqs,
        $default,
        $prefix = '',
        $suffix = '',
        $dispatchAfterCommit = false,
        array $fifoConfig = array()
    ) {
        parent::__construct($sqs, $default, $prefix, $suffix, $dispatchAfterCommit);

        $this->fifoConfig = array_merge(array(
            'group' => 'default',
            'deduplicator' => new Content(),
            'allow_delay' => false,
        ), $fifoConfig);
    }

    public function pushRaw($payload, $queue = null, array $options = array())
    {
        $params = array(
            'QueueUrl' => $this->getQueue($queue),
            'MessageBody' => $payload,
            'MessageGroupId' => $this->resolveMessageGroup($payload, $options),
        );

        $deduplicationId = $this->resolveDeduplicationId($payload, $options);

        if ($deduplicationId !== false && $deduplicationId !== null && $deduplicationId !== '') {
            $params['MessageDeduplicationId'] = $deduplicationId;
        }

        return $this->getSqs()->sendMessage($params)->get('MessageId');
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        if (empty($this->fifoConfig['allow_delay'])) {
            throw new BadMethodCallException('FIFO queues do not support per-message delay.');
        }

        if (class_exists(Log::class)) {
            Log::warning('SQS FIFO per-message delay was ignored; configure queue-level delay instead.');
        }

        return $this->push($job, $data, $queue);
    }

    protected function resolveMessageGroup($payload, array $options)
    {
        if (!empty($options['MessageGroupId'])) {
            return (string) $options['MessageGroupId'];
        }

        $decoded = json_decode((string) $payload, true);

        if (is_array($decoded) && isset($decoded['sqs_fifo']['group'])) {
            return (string) $decoded['sqs_fifo']['group'];
        }

        return (string) $this->fifoConfig['group'];
    }

    protected function resolveDeduplicationId($payload, array $options)
    {
        if (!empty($options['MessageDeduplicationId'])) {
            return (string) $options['MessageDeduplicationId'];
        }

        $deduplicator = $this->fifoConfig['deduplicator'];

        if (is_string($deduplicator) && class_exists($deduplicator)) {
            $deduplicator = new $deduplicator();
        }

        if (!$deduplicator instanceof Deduplicator) {
            $deduplicator = new Content();
        }

        return $deduplicator->deduplicate((string) $payload);
    }
}
