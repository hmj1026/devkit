<?php

namespace Devkit\Tests\Laravel\Database\Fixture;

use Devkit\Logging\Contract\LogTargetContract;

class RecordingLogTarget implements LogTargetContract
{
    /**
     * @var array<int, array>
     */
    public $entries = array();

    public function save(array $entry)
    {
        $this->entries[] = $entry;
    }
}
