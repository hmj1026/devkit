# devkit-elasticsearch Specification

## Purpose
Multi-connection Elasticsearch manager, declarative Index/Alias bases, AWS-signed handler, and Artisan index lifecycle commands — no query builder layer.

## Requirements

### Requirement: ES Client Manager (Multiple Connections)
`Devkit\Search\Client\ElasticsearchManager` SHALL manage multiple named connections, each producing an `Elasticsearch\Client` instance (elasticsearch-php ^7.17).

#### Scenario: Multiple connections from config
- **WHEN** config declares `default` and `audit` connections with different hosts
- **THEN** `$manager->connection('default')` and `$manager->connection('audit')` return distinct `Client` instances pointing at the respective hosts

#### Scenario: Default connection
- **WHEN** code calls `$manager->connection()` without name
- **THEN** the connection declared as default in config is returned

### Requirement: Declarative Index Mapping Base
`Devkit\Search\Index\Index` SHALL provide an Eloquent-like base for index documents, with `save()` / `create()` / `delete()` / `getIndex()` / `getMapping()` and partitioning via `getPartition()`. Subclasses declare mapping as a `getMapping(): array` returning native ES mapping DSL.

#### Scenario: Save document to monthly partition
- **WHEN** a subclass declares `getPartition()` returning `'2026-05'` and code calls `$doc->save()`
- **THEN** the document is indexed into `<base_index>-2026-05`

#### Scenario: Read mapping from subclass
- **WHEN** code calls `MyIndex::createMapping()`
- **THEN** the underlying ES client creates the index with the mapping returned by the subclass

### Requirement: Alias Management with Filters
`Devkit\Search\Index\Alias` SHALL support filter-by-document on alias creation.

#### Scenario: Filtered alias
- **WHEN** an alias is created with filter `['term' => ['tenant_id' => 42]]`
- **THEN** queries via the alias only return documents matching that filter

### Requirement: No Query Builder or Grammar Translation Layer
This package SHALL NOT ship `Builder`, `QueryBuilder`, or any `Grammar\*` translator classes. Consumers run searches by passing the native ES request body directly to `Elasticsearch\Client::search($params)`. Reduced surface = no fake abstraction over raw ES DSL.

#### Scenario: Audit src/ has no Builder
- **WHEN** a maintainer greps `src/Search/` for `class Builder` or `class QueryBuilder`
- **THEN** no such classes are found

#### Scenario: Native ES query works
- **WHEN** code calls `$manager->connection()->search(['index' => 'my-index', 'body' => ['query' => ['match_all' => (object) []]]])`
- **THEN** results are returned exactly as the native ES client provides them

### Requirement: AWS Signed Request Handler (Optional)
The toolkit SHALL provide an optional `AwsSignedHandler` for AWS-managed Elasticsearch, signing requests via AWS SDK credentials.

#### Scenario: AWS signed request
- **WHEN** the manager is configured with AWS credentials and code dispatches a search
- **THEN** the outgoing HTTP request includes a valid `Authorization: AWS4-HMAC-SHA256 ...` header

### Requirement: Laravel Artisan Commands for Index Lifecycle
A Laravel adapter SHALL register 6 Artisan commands: `index:create`, `index:delete`, `index:exists`, `index:update-mapping`, `alias:switch`, `reindex`. The previous count (8) is reduced by merging alias-create / alias-remove-index into a single `alias:switch`.

#### Scenario: Create index command
- **WHEN** an operator runs `php artisan devkit:elasticsearch:index:create MyIndex`
- **THEN** the corresponding ES index is created with the mapping declared on `MyIndex`
