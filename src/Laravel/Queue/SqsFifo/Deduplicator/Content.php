<?php

namespace Devkit\Laravel\Queue\SqsFifo\Deduplicator;

use Devkit\Laravel\Queue\SqsFifo\Contract\Deduplicator;

class Content implements Deduplicator
{
    public function deduplicate($payload)
    {
        return hash('sha256', (string) $payload);
    }
}
