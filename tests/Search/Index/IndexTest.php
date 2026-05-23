<?php

namespace Devkit\Tests\Search\Index;

use Devkit\Tests\Search\Index\Fixture\TestIndex;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSaveSendsIndexAndBodyToEsClient()
    {
        $client = Mockery::mock();
        $client->shouldReceive('index')
            ->once()
            ->with(array(
                'index' => 'devkit-test',
                'body' => array('foo' => 'bar', 'count' => 7),
            ))
            ->andReturn(array('result' => 'created'));

        $doc = new TestIndex($client, array('foo' => 'bar'));
        $response = $doc->save(array('count' => 7));

        $this->assertSame('created', $response['result']);
        $this->assertSame(array('foo' => 'bar', 'count' => 7), $doc->getAttributes());
    }

    public function testSaveWithDocumentIdSendsIdParam()
    {
        $client = Mockery::mock();
        $client->shouldReceive('index')
            ->once()
            ->with(array(
                'index' => 'devkit-test',
                'body' => array('foo' => 'bar'),
                'id' => 'doc-1',
            ))
            ->andReturn(array('result' => 'created'));

        $doc = new TestIndex($client, array('foo' => 'bar'), 'doc-1');
        $doc->save();

        $this->assertSame('doc-1', $doc->getDocumentId());
    }

    public function testGetPartitionSuffixesResolvedIndex()
    {
        $client = Mockery::mock();
        $doc = new TestIndex($client);
        $doc->partition = '2026-05';

        $this->assertSame('devkit-test-2026-05', $doc->getResolvedIndex());

        $client->shouldReceive('index')
            ->once()
            ->with(Mockery::on(function ($args) {
                return $args['index'] === 'devkit-test-2026-05';
            }))
            ->andReturn(array('result' => 'created'));

        $doc->save(array('x' => 1));
    }

    public function testCreatePassesMappingUnchanged()
    {
        $mapping = array(
            'properties' => array(
                'foo' => array('type' => 'keyword'),
                'when' => array('type' => 'date'),
            ),
        );

        $indices = Mockery::mock();
        $indices->shouldReceive('create')
            ->once()
            ->with(array(
                'index' => 'devkit-test',
                'body' => array('mappings' => $mapping),
            ))
            ->andReturn(array('acknowledged' => true));

        $client = Mockery::mock();
        $client->shouldReceive('indices')->andReturn($indices);

        $doc = new TestIndex($client);
        $doc->mapping = $mapping;

        $response = $doc->create();
        $this->assertTrue($response['acknowledged']);
    }

    public function testCreateMergesSettingsWhenDeclared()
    {
        $indices = Mockery::mock();
        $indices->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($args) {
                return $args['index'] === 'devkit-test'
                    && isset($args['body']['mappings'])
                    && isset($args['body']['settings']['number_of_shards'])
                    && $args['body']['settings']['number_of_shards'] === 3;
            }))
            ->andReturn(array('acknowledged' => true));

        $client = Mockery::mock();
        $client->shouldReceive('indices')->andReturn($indices);

        $doc = new TestIndex($client);
        $doc->settings = array('number_of_shards' => 3);
        $doc->create();
    }

    public function testDeleteCallsIndicesDelete()
    {
        $indices = Mockery::mock();
        $indices->shouldReceive('delete')
            ->once()
            ->with(array('index' => 'devkit-test'))
            ->andReturn(array('acknowledged' => true));

        $client = Mockery::mock();
        $client->shouldReceive('indices')->andReturn($indices);

        (new TestIndex($client))->delete();
    }

    public function testUpdateMappingCallsPutMapping()
    {
        $mapping = array('properties' => array('foo' => array('type' => 'keyword')));

        $indices = Mockery::mock();
        $indices->shouldReceive('putMapping')
            ->once()
            ->with(array(
                'index' => 'devkit-test',
                'body' => $mapping,
            ))
            ->andReturn(array('acknowledged' => true));

        $client = Mockery::mock();
        $client->shouldReceive('indices')->andReturn($indices);

        (new TestIndex($client))->updateMapping();
    }
}
