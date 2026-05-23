<?php

namespace Devkit\Tests\Laravel\Audit;

use Devkit\Laravel\Audit\AgentSupport;
use Devkit\Laravel\Audit\ElasticsearchLogTarget;
use Devkit\Laravel\Audit\EloquentLogTarget;
use Devkit\Tests\Laravel\TestCase;
use Devkit\Tests\Search\Index\Fixture\TestIndex;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class AuditLoggingTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('article_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('entity_id')->nullable();
            $table->string('action');
            $table->text('changes');
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('article_logs');

        parent::tearDown();
    }

    public function testEloquentLogTargetWritesEntryToConfiguredTable()
    {
        $target = new EloquentLogTarget('article_logs');
        $target->save(array(
            'entity_id' => 10,
            'action' => 'updated',
            'changes' => array('title' => array('from' => 'a', 'to' => 'b')),
            'user_id' => null,
            'created_at' => '2026-05-23 00:00:00',
        ));

        $row = DB::table('article_logs')->first();

        $this->assertSame(10, (int) $row->entity_id);
        $this->assertSame('updated', $row->action);
        $this->assertStringContainsString('"title"', $row->changes);
    }

    public function testElasticsearchLogTargetDelegatesToIndex()
    {
        $client = Mockery::mock();
        $client->shouldReceive('index')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['index'] === 'devkit-test'
                    && $params['body']['action'] === 'updated';
            }))
            ->andReturn(array('result' => 'created'));

        $target = new ElasticsearchLogTarget(new TestIndex($client));
        $target->save(array('entity_id' => 10, 'action' => 'updated', 'changes' => array()));
    }

    public function testAgentSupportMasksSensitiveHeaders()
    {
        $headers = AgentSupport::sanitizeHeaders(array(
            'authorization' => array('Bearer secret'),
            'cookie' => array('a=b'),
            'x-request-id' => array('req-1'),
        ));

        $this->assertSame(array('[redacted]'), $headers['authorization']);
        $this->assertSame(array('[redacted]'), $headers['cookie']);
        $this->assertSame(array('req-1'), $headers['x-request-id']);
    }
}
