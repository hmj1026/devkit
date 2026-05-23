<?php

namespace Devkit\Tests\Laravel\Database;

use Devkit\Laravel\Database\Criteria;
use Devkit\Tests\Laravel\Database\Fixture\Article;
use Devkit\Tests\Laravel\Database\Fixture\RecordingLogTarget;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EntityTraitsAndCriteriaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->nullable()->unique();
            $table->string('title');
            $table->integer('status')->default(0);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('articles');

        parent::tearDown();
    }

    public function testHasUuidAutoGeneratesUuidAndFindsByUuid()
    {
        $article = Article::create(array('title' => 'draft'));

        $this->assertNotEmpty($article->getUuid());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $article->getUuid()
        );
        $this->assertTrue($article->is(Article::findByUuid($article->getUuid())));
    }

    public function testHasStatusActivatesAndDeactivates()
    {
        $article = Article::create(array('title' => 'draft', 'status' => 0));

        $article->activate();
        $this->assertTrue($article->fresh()->isActive());
        $this->assertSame(1, $article->fresh()->status);

        $article->deactivate();
        $this->assertFalse($article->fresh()->isActive());
        $this->assertSame(0, $article->fresh()->status);
    }

    public function testHasAuditLogWritesUpdateDiffOnce()
    {
        $target = new RecordingLogTarget();
        Article::setAuditLogTarget($target);

        $article = Article::create(array('title' => 'draft', 'status' => 0));
        $target->entries = array();

        $article->title = 'published';
        $article->save();

        $this->assertCount(1, $target->entries);
        $this->assertSame('updated', $target->entries[0]['action']);
        $this->assertSame($article->getKey(), $target->entries[0]['entity_id']);
        $this->assertSame(
            array('from' => 'draft', 'to' => 'published'),
            $target->entries[0]['changes']['title']
        );

        Article::setAuditLogTarget(null);
    }

    public function testCriteriaAppliesReusableQueryShape()
    {
        Article::create(array('title' => 'old', 'status' => 1));
        Article::create(array('title' => 'new', 'status' => 1));
        Article::create(array('title' => 'hidden', 'status' => 0));

        $criteria = Criteria::create()
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->limit(1);

        $result = Article::query()->withCriteria($criteria)->first();

        $this->assertSame('new', $result->title);
    }
}
