<?php

namespace Devkit\Laravel\Audit;

use Devkit\Logging\Contract\LogTargetContract;
use Devkit\Search\Contract\IndexContract;

class ElasticsearchLogTarget implements LogTargetContract
{
    /**
     * @var IndexContract
     */
    protected $index;

    public function __construct(IndexContract $index)
    {
        $this->index = $index;
    }

    public function save(array $entry)
    {
        $this->index->save($entry);
    }
}
