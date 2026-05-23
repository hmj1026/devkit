<?php

namespace Devkit\Laravel\Queue\SqsFifo;

trait SqsFifoQueueable
{
    /**
     * @var string|null
     */
    public $sqsMessageGroup;

    public function onMessageGroup($group)
    {
        $this->sqsMessageGroup = (string) $group;

        return $this;
    }

    public function messageGroup()
    {
        return $this->sqsMessageGroup;
    }
}
