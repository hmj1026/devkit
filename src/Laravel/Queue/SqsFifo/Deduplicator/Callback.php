<?php

namespace Devkit\Laravel\Queue\SqsFifo\Deduplicator;

use Closure;
use Devkit\Laravel\Queue\SqsFifo\Contract\Deduplicator;

class Callback implements Deduplicator
{
    /**
     * @var Closure
     */
    protected $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function deduplicate($payload)
    {
        return call_user_func($this->callback, $payload);
    }
}
