<?php

return array(
    'modules' => array(
        'logging' => array('enabled' => true),
        'http' => array('enabled' => true),
        'storage' => array('enabled' => true),
        'search' => array('enabled' => true),
        'database' => array('enabled' => true),
        'messaging' => array('enabled' => true),
        'ui' => array('enabled' => true),
        'queue' => array('enabled' => true),
        'commands' => array('enabled' => true),
    ),
    'disks' => array('default' => 'local'),
    'sms' => array(
        'default' => 'null',
        'drivers' => array(),
        'queue' => array('connection' => null, 'queue' => null),
    ),
    'http' => array(
        'asset_version' => array('cache_key' => 'devkit.asset_version', 'ttl' => 3600),
        'gateway' => array('max_attempts' => 3, 'base_delay_ms' => 100),
    ),
    'search' => array(
        'default' => 'default',
        'connections' => array('default' => array('hosts' => array('localhost:9200'))),
    ),
    'googlechat' => array('url' => null, 'mentions' => array()),
    'audit' => array('target' => null, 'login_target' => null),
    'commands' => array('generators' => array('enabled' => false)),
);
