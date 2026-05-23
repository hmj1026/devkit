<?php

namespace Devkit\Tests\Search\Index\Fixture;

use Devkit\Search\Index\Index;

/**
 * Minimal Index subclass used by IndexTest. Mapping/index/partition
 * are mutable so a single fixture can exercise every variant the
 * test needs.
 */
class TestIndex extends Index
{
    /** @var string */
    public $indexName = 'devkit-test';

    /** @var array */
    public $mapping = array('properties' => array('foo' => array('type' => 'keyword')));

    /** @var string|null */
    public $partition;

    /** @var array */
    public $settings = array();

    public function getIndex()
    {
        return $this->indexName;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    public function getPartition()
    {
        return $this->partition;
    }

    public function getSettings()
    {
        return $this->settings;
    }
}
