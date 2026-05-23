<?php

namespace Devkit\Laravel\Queue\SqsFifo\Deduplicator;

use Devkit\Laravel\Queue\SqsFifo\Contract\Deduplicator;
use Illuminate\Support\Str;

class Unique implements Deduplicator
{
    public function deduplicate($payload)
    {
        return (string) Str::uuid();
    }
}
