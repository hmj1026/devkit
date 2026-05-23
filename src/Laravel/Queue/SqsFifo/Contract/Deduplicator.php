<?php

namespace Devkit\Laravel\Queue\SqsFifo\Contract;

interface Deduplicator
{
    /**
     * @param  string  $payload
     * @return string|false
     */
    public function deduplicate($payload);
}
