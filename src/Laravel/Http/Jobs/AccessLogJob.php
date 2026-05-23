<?php

namespace Devkit\Laravel\Http\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class AccessLogJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @var array
     */
    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function payload()
    {
        return $this->payload;
    }

    public function handle()
    {
        return $this->payload;
    }
}
