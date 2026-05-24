<?php

namespace Devkit\Search\Contract;

/**
 * Named Elasticsearch connection — wraps the configuration needed to
 * obtain a configured elasticsearch-php client. The concrete manager
 * (Devkit\Search\Client\ElasticsearchManager) resolves
 * connections by name; consumers SHOULD type-hint this contract when
 * accepting "any ES connection".
 *
 * Pure PHP — no Illuminate imports.
 */
interface ConnectionContract
{
    /**
     * Return the configured elasticsearch-php client for this connection.
     * Implementations MAY return a cached instance.
     *
     * @return \Elasticsearch\Client  elasticsearch-php ^7.x; v2 (Elastic\Elasticsearch\Client, SDK 8.x) is a separate future contract.
     */
    public function getClient();

    /**
     * Return the connection's logical name as registered with the manager.
     *
     * @return string
     */
    public function getName();
}
