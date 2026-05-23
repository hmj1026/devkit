<?php

namespace Devkit\Tests\Search\Index;

use Devkit\Search\Index\Alias;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AliasTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRenderEmptyByDefault()
    {
        $this->assertSame(array(), (new Alias('latest'))->render());
    }

    public function testRenderIncludesFilterAndRouting()
    {
        $alias = new Alias('tenant-42', array('term' => array('tenant_id' => 42)), 'r-42');

        $this->assertSame(
            array(
                'filter' => array('term' => array('tenant_id' => 42)),
                'routing' => 'r-42',
            ),
            $alias->render()
        );
    }

    public function testRenderIncludesWriteIndexFlag()
    {
        $alias = new Alias('writer');
        $alias->setIsWriteIndex(true);

        $this->assertTrue($alias->render()['is_write_index']);
    }

    public function testPutAliasCallsClientWithExpectedParams()
    {
        $indices = Mockery::mock();
        $indices->shouldReceive('putAlias')
            ->once()
            ->with(array(
                'index' => 'devkit-test-2026-05',
                'name' => 'tenant-42',
                'body' => array(
                    'filter' => array('term' => array('tenant_id' => 42)),
                ),
            ))
            ->andReturn(array('acknowledged' => true));

        $client = Mockery::mock();
        $client->shouldReceive('indices')->andReturn($indices);

        $alias = new Alias('tenant-42', array('term' => array('tenant_id' => 42)));
        $result = $alias->putAlias($client, 'devkit-test-2026-05');

        $this->assertTrue($result['acknowledged']);
    }

    public function testPutAliasSkipsBodyWhenNoMetadata()
    {
        $indices = Mockery::mock();
        $indices->shouldReceive('putAlias')
            ->once()
            ->with(Mockery::on(function ($args) {
                return $args['index'] === 'devkit-test'
                    && $args['name'] === 'latest'
                    && !isset($args['body']);
            }))
            ->andReturn(array('acknowledged' => true));

        $client = Mockery::mock();
        $client->shouldReceive('indices')->andReturn($indices);

        (new Alias('latest'))->putAlias($client, 'devkit-test');
    }
}
