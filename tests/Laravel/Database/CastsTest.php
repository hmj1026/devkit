<?php

namespace Devkit\Tests\Laravel\Database;

use Devkit\Tests\Laravel\Database\Fixture\SensitiveRecord;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CastsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sensitive_records', function (Blueprint $table) {
            $table->increments('id');
            $table->text('ssn')->nullable();
            $table->string('password')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sensitive_records');

        parent::tearDown();
    }

    public function testEncryptedCastRoundTripsPlainValue()
    {
        $record = SensitiveRecord::create(array('ssn' => 'A123456789'));
        $raw = SensitiveRecord::query()->toBase()->where('id', $record->id)->value('ssn');

        $this->assertNotSame('A123456789', $raw);
        $this->assertSame('A123456789', $record->fresh()->ssn);
    }

    public function testHashedCastStoresOneWayHash()
    {
        $record = SensitiveRecord::create(array('password' => 'plain'));
        $raw = SensitiveRecord::query()->toBase()->where('id', $record->id)->value('password');

        $this->assertNotSame('plain', $raw);
        $this->assertTrue(Hash::check('plain', $raw));
        $this->assertSame($raw, $record->fresh()->password);
    }
}
