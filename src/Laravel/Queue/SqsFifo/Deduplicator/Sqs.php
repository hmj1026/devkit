<?php

namespace Devkit\Laravel\Queue\SqsFifo\Deduplicator;

use Devkit\Laravel\Queue\SqsFifo\Contract\Deduplicator;

class Sqs implements Deduplicator
{
    public function deduplicate($payload)
    {
        return false;
    }
}
