<?php

namespace Devkit\Laravel\Queue\SqsFifo;

use Aws\Sqs\SqsClient;
use Devkit\Laravel\Queue\SqsFifo\Deduplicator\Content;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class SqsFifoConnector implements ConnectorInterface
{
    public function connect(array $config)
    {
        if (empty($config['queue']) || substr($config['queue'], -5) !== '.fifo') {
            throw new InvalidArgumentException('SQS FIFO queue name must end with .fifo.');
        }

        $config = array_merge(array(
            'version' => 'latest',
            'http' => array('timeout' => 60, 'connect_timeout' => 60),
            'group' => 'default',
            'deduplicator' => new Content(),
            'allow_delay' => false,
        ), $config);

        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, array('key', 'secret', 'token'));
        }

        return new SqsFifoQueue(
            new SqsClient($config),
            $config['queue'],
            isset($config['prefix']) ? $config['prefix'] : '',
            isset($config['suffix']) ? $config['suffix'] : '',
            isset($config['after_commit']) ? $config['after_commit'] : false,
            array(
                'group' => $config['group'],
                'deduplicator' => $config['deduplicator'],
                'allow_delay' => $config['allow_delay'],
            )
        );
    }
}
