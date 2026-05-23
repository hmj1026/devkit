<?php

namespace Devkit\Search\Index;

/**
 * Represents an Elasticsearch alias declaration. Holds the alias name
 * plus optional filter / routing settings, and knows how to render
 * itself into the body format expected by the ES indices alias APIs.
 *
 * Pure PHP — no Illuminate imports.
 */
class Alias
{
    /** @var string */
    protected $alias;

    /** @var array|null */
    protected $filter;

    /** @var string|null */
    protected $routing;

    /** @var bool */
    protected $isWriteIndex = false;

    /**
     * @param  string  $alias
     * @param  array|null  $filter   Optional ES filter expression to apply per-document.
     * @param  string|null  $routing Optional routing value.
     */
    public function __construct($alias, array $filter = null, $routing = null)
    {
        $this->alias = (string) $alias;
        $this->filter = $filter;
        $this->routing = $routing === null ? null : (string) $routing;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return array|null
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param  array|null  $filter
     * @return $this
     */
    public function setFilter(array $filter = null)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRouting()
    {
        return $this->routing;
    }

    /**
     * @param  string|null  $routing
     * @return $this
     */
    public function setRouting($routing)
    {
        $this->routing = $routing === null ? null : (string) $routing;

        return $this;
    }

    /**
     * @return bool
     */
    public function isWriteIndex()
    {
        return $this->isWriteIndex;
    }

    /**
     * @param  bool  $isWriteIndex
     * @return $this
     */
    public function setIsWriteIndex($isWriteIndex)
    {
        $this->isWriteIndex = (bool) $isWriteIndex;

        return $this;
    }

    /**
     * Render this alias as the body payload portion ES expects when
     * declaring aliases on an index (e.g. inside indices()->create()).
     *
     * @return array
     */
    public function render()
    {
        $body = array();

        if ($this->filter !== null) {
            $body['filter'] = $this->filter;
        }
        if ($this->routing !== null) {
            $body['routing'] = $this->routing;
        }
        if ($this->isWriteIndex) {
            $body['is_write_index'] = true;
        }

        return $body;
    }

    /**
     * Attach this alias to the given index via indices()->putAlias().
     *
     * @param  \Elasticsearch\Client  $client
     * @param  string  $index
     * @return mixed
     */
    public function putAlias($client, $index)
    {
        $params = array(
            'index' => $index,
            'name' => $this->alias,
        );

        $body = $this->render();
        if ($body !== array()) {
            $params['body'] = $body;
        }

        return $client->indices()->putAlias($params);
    }
}
