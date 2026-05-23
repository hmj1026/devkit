<?php

namespace Devkit\Search\Index;

use Devkit\Search\Contract\IndexContract;

/**
 * Eloquent-like base for Elasticsearch index documents. Subclasses
 * declare the index name and mapping; this class handles document
 * persistence, create/delete/updateMapping plumbing, and (optionally)
 * partitioned writes through getPartition().
 *
 * The spec is explicit: this is NOT a query builder. Reads are done
 * by calling `$client->search([...])` directly with the native ES DSL.
 *
 * Pure PHP — no Illuminate imports.
 */
abstract class Index implements IndexContract
{
    /** @var \Elasticsearch\Client */
    protected $client;

    /** @var array<string, mixed> */
    protected $attributes = array();

    /** @var string|null  Optional document _id. Null → ES auto-id. */
    protected $documentId;

    /**
     * @param  \Elasticsearch\Client  $client
     * @param  array<string, mixed>  $attributes  Initial document body.
     * @param  string|null  $id  Optional document _id.
     */
    public function __construct($client, array $attributes = array(), $id = null)
    {
        $this->client = $client;
        $this->attributes = $attributes;
        $this->documentId = $id === null ? null : (string) $id;
    }

    /**
     * Subclasses MUST return the base index name (no partition suffix).
     *
     * @return string
     */
    abstract public function getIndex();

    /**
     * Subclasses MUST return the native ES mapping DSL.
     *
     * @return array
     */
    abstract public function getMapping();

    /**
     * Default: unpartitioned. Subclasses override to return the
     * partition suffix (e.g. "2026-05") for date-bucketed indices.
     *
     * @return string|null
     */
    public function getPartition()
    {
        return null;
    }

    /**
     * Optional index settings (shards, replicas, analyzers...).
     * Subclasses override when they need anything beyond ES defaults.
     *
     * @return array
     */
    public function getSettings()
    {
        return array();
    }

    /**
     * Optional alias map: alias name → alias body.
     *
     * @return array
     */
    public function getAliases()
    {
        return array();
    }

    /**
     * Resolve the concrete index name including partition suffix.
     *
     * @return string
     */
    public function getResolvedIndex()
    {
        $partition = $this->getPartition();

        return $partition === null || $partition === ''
            ? $this->getIndex()
            : $this->getIndex() . '-' . $partition;
    }

    /**
     * Mass-assign attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $k => $v) {
            $this->attributes[(string) $k] = $v;
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return string|null
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param  string|null  $id
     * @return $this
     */
    public function setDocumentId($id)
    {
        $this->documentId = $id === null ? null : (string) $id;

        return $this;
    }

    /**
     * Persist the document via `$client->index()`. When `$attributes`
     * is non-empty, merges into the model's current attributes first.
     *
     * @param  array<string, mixed>  $attributes
     * @return mixed  Raw ES client response.
     */
    public function save(array $attributes = array())
    {
        if ($attributes !== array()) {
            $this->fill($attributes);
        }

        $params = array(
            'index' => $this->getResolvedIndex(),
            'body' => $this->attributes,
        );

        if ($this->documentId !== null && $this->documentId !== '') {
            $params['id'] = $this->documentId;
        }

        return $this->client->index($params);
    }

    /**
     * Create the underlying index with the declared mapping and settings.
     *
     * @return mixed
     */
    public function create()
    {
        $body = array('mappings' => $this->getMapping());

        $settings = $this->getSettings();
        if ($settings !== array()) {
            $body['settings'] = $settings;
        }

        $aliases = $this->getAliases();
        if ($aliases !== array()) {
            $body['aliases'] = $aliases;
        }

        return $this->client->indices()->create(array(
            'index' => $this->getResolvedIndex(),
            'body' => $body,
        ));
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->client->indices()->delete(array(
            'index' => $this->getResolvedIndex(),
        ));
    }

    /**
     * Apply the subclass-declared mapping on top of the live index.
     *
     * @return mixed
     */
    public function updateMapping()
    {
        return $this->client->indices()->putMapping(array(
            'index' => $this->getResolvedIndex(),
            'body' => $this->getMapping(),
        ));
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return (bool) $this->client->indices()->exists(array(
            'index' => $this->getResolvedIndex(),
        ));
    }

    /**
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
