# Devkit Elasticsearch

## Use Case

Use `ElasticsearchManager` for named Elasticsearch connections and `Index` / `Alias` bases for document lifecycle helpers. Query bodies stay native Elasticsearch array DSL.

## Laravel Configuration

```php
'modules' => array(
    'search' => array('enabled' => true),
),
'search' => array(
    'default' => 'default',
    'connections' => array(
        'default' => array('hosts' => array('localhost:9200')),
        'audit' => array('hosts' => array('localhost:9201')),
    ),
),
```

## Pure PHP Usage

```php
$manager = new ElasticsearchManager('default');
$manager->extend('default', function () {
    return \Elasticsearch\ClientBuilder::create()
        ->setHosts(array('localhost:9200'))
        ->build();
});

$results = $manager->connection()->search(array(
    'index' => 'orders',
    'body' => array('query' => array('match_all' => (object) array())),
));
```
