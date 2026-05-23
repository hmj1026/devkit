<?php

namespace Devkit\Tests\Search\Client;

use Devkit\Search\Client\ElasticsearchManager;
use Devkit\Search\Client\Exception\ConnectionNotRegisteredException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ElasticsearchManagerTest extends TestCase
{
    public function testExtendAndConnectionReturnsRegisteredInstance()
    {
        $manager = new ElasticsearchManager('default');
        $client = new stdClass();
        $client->tag = 'default-client';

        $manager->extend('default', function () use ($client) {
            return $client;
        });

        $this->assertSame($client, $manager->connection());
        $this->assertSame($client, $manager->connection('default'));
    }

    public function testConnectionCachesPerName()
    {
        $manager = new ElasticsearchManager('default');
        $calls = 0;
        $manager->extend('default', function () use (&$calls) {
            $calls++;

            return new stdClass();
        });

        $first = $manager->connection();
        $second = $manager->connection();

        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
    }

    public function testInitialFactoriesArePreloadedFromConstructor()
    {
        $client = new stdClass();
        $manager = new ElasticsearchManager('primary', array(
            'primary' => function () use ($client) {
                return $client;
            },
        ));

        $this->assertSame($client, $manager->connection());
        $this->assertSame(array('primary'), $manager->getConnectionNames());
    }

    public function testDistinctConnectionsReturnDistinctClients()
    {
        $manager = new ElasticsearchManager('default');
        $a = new stdClass();
        $b = new stdClass();
        $manager->extend('default', function () use ($a) {
            return $a;
        });
        $manager->extend('audit', function () use ($b) {
            return $b;
        });

        $this->assertSame($a, $manager->connection('default'));
        $this->assertSame($b, $manager->connection('audit'));
        $this->assertNotSame($manager->connection('default'), $manager->connection('audit'));
    }

    public function testUnknownConnectionThrows()
    {
        $manager = new ElasticsearchManager('default');

        $this->expectException(ConnectionNotRegisteredException::class);
        $manager->connection('nope');
    }

    public function testForgetDropsCachedInstance()
    {
        $manager = new ElasticsearchManager('default');
        $calls = 0;
        $manager->extend('default', function () use (&$calls) {
            $calls++;

            return new stdClass();
        });

        $manager->connection();
        $manager->forget('default');
        $manager->connection();

        $this->assertSame(2, $calls);
    }

    public function testExtendReplacesFactory()
    {
        $manager = new ElasticsearchManager('default');
        $manager->extend('default', function () {
            return (object) array('v' => 1);
        });
        $manager->connection(); // resolve once
        $manager->extend('default', function () {
            return (object) array('v' => 2);
        });

        $this->assertSame(2, $manager->connection()->v);
    }

    public function testSetDefaultConnection()
    {
        $manager = new ElasticsearchManager('default');
        $manager->extend('audit', function () {
            return 'audit-client';
        });
        $manager->setDefaultConnection('audit');

        $this->assertSame('audit', $manager->getDefaultConnection());
        $this->assertSame('audit-client', $manager->connection());
    }
}
