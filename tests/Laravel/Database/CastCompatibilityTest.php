<?php

namespace Devkit\Tests\Laravel\Database;

use Devkit\Laravel\Database\Cast\EncryptedCast;
use Devkit\Laravel\Database\Cast\HashedCast;
use Devkit\Laravel\Database\Cast\UsesClassCastCompatibility;
use Devkit\Tests\Laravel\Database\Fixture\CountedRecord;
use Devkit\Tests\Laravel\Database\Fixture\CountingCast;
use Devkit\Tests\Laravel\Database\Fixture\SensitiveRecord;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;

class CastCompatibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sensitive_records', function (Blueprint $table) {
            $table->increments('id');
            $table->text('ssn')->nullable();
            $table->string('password')->nullable();
        });

        Schema::create('counted_records', function (Blueprint $table) {
            $table->increments('id');
            $table->string('col')->nullable();
        });

        CountingCast::reset();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('counted_records');
        Schema::dropIfExists('sensitive_records');

        parent::tearDown();
    }

    private function skipIfNativeCastsActive(string $reason): void
    {
        $native = interface_exists('Illuminate\\Contracts\\Database\\Eloquent\\CastsAttributes', false)
            && ! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED');

        if ($native) {
            $this->markTestSkipped($reason);
        }
    }

    public function testToArrayDecryptsEncryptedCast()
    {
        $record = SensitiveRecord::create(array('ssn' => 'A123456789'))->fresh();

        $this->assertSame('A123456789', $record->toArray()['ssn']);
    }

    public function testToJsonContainsDecryptedSsn()
    {
        $record = SensitiveRecord::create(array('ssn' => 'A123456789'))->fresh();
        $decoded = json_decode($record->toJson(), true);

        $this->assertSame('A123456789', $decoded['ssn']);
    }

    public function testReSettingSameEncryptedValueIsNotDirty()
    {
        $this->skipIfNativeCastsActive('Decoded-value dirty equivalence is a polyfill-path guarantee; native L7+ compares raw bytes.');

        $record = SensitiveRecord::create(array('ssn' => 'A123456789'));
        $record->refresh();

        $record->ssn = 'A123456789';

        $this->assertFalse($record->isDirty('ssn'), 'Re-assigning the same plaintext should not flip isDirty.');
    }

    public function testGenuineValueChangeStillFlagsDirty()
    {
        $record = SensitiveRecord::create(array('ssn' => 'A123456789'));
        $record->refresh();

        $record->ssn = 'B987654321';

        $this->assertTrue($record->isDirty('ssn'));
    }

    public function testHashedCastDirtyEquivalence()
    {
        $this->skipIfNativeCastsActive('Decoded-value dirty equivalence is a polyfill-path guarantee; native L7+ compares raw bytes (and a future bcrypt cost bump would flip this).');

        $record = SensitiveRecord::create(array('password' => 'plain'));
        $record->refresh();

        $record->password = $record->password;

        $this->assertFalse($record->isDirty('password'), 'Re-assigning the stored hash should not flip isDirty.');
    }

    public function testRepeatedReadsCallCastGetOnce()
    {
        $this->skipIfNativeCastsActive('Scalar cast results are not memoized by native L7+ classCastCache (it only stores objects); polyfill caches all.');

        $record = CountedRecord::create(array('col' => 'value'))->fresh();
        CountingCast::reset();

        for ($i = 0; $i < 5; $i++) {
            $unused = $record->col;
        }

        $this->assertSame(1, CountingCast::$getCount, 'Cached cast should call get() once per instance.');
    }

    public function testWritingAttributeInvalidatesCache()
    {
        $record = CountedRecord::create(array('col' => 'value'))->fresh();
        CountingCast::reset();

        $_ = $record->col;
        $record->col = 'new-value';
        $_ = $record->col;

        $this->assertSame(2, CountingCast::$getCount, 'setAttribute should invalidate the cache for that key.');
    }

    public function testSetRawAttributesClearsCache()
    {
        $record = CountedRecord::create(array('col' => 'value'))->fresh();
        CountingCast::reset();

        $_ = $record->col;
        $record->setRawAttributes($record->getAttributes(), true);
        $_ = $record->col;

        $this->assertSame(2, CountingCast::$getCount, 'setRawAttributes should clear the entire cache.');
    }

    public function testNativeDetectionUsesInterfacePresence()
    {
        $traitSource = file_get_contents(
            __DIR__ . '/../../../src/Laravel/Database/Cast/UsesClassCastCompatibility.php'
        );
        $this->assertIsString($traitSource, 'Could not read trait source file.');
        $this->assertStringNotContainsString(
            'isClassCastable',
            $traitSource,
            'Detection must not probe the protected isClassCastable helper.'
        );

        $record = new SensitiveRecord();
        $method = new ReflectionMethod($record, 'hasNativeClassCasts');
        $method->setAccessible(true);

        $expectedNative = interface_exists('Illuminate\\Contracts\\Database\\Eloquent\\CastsAttributes', false)
            && ! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED');

        $this->assertSame(
            $expectedNative,
            $method->invoke($record),
            'hasNativeClassCasts must mirror "interface present AND polyfill marker absent".'
        );
    }

    public function testEncryptedCastSetReturnsScalar()
    {
        $value = (new EncryptedCast())->set(new SensitiveRecord(), 'ssn', 'plain', array());

        $this->assertIsString($value);
    }

    public function testHashedCastSetReturnsScalar()
    {
        $value = (new HashedCast())->set(new SensitiveRecord(), 'password', 'plain', array());

        $this->assertIsString($value);
    }
}
