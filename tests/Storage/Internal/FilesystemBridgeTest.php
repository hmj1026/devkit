<?php

namespace Devkit\Tests\Storage\Internal;

use Devkit\Storage\Exception\StorageException;
use Devkit\Storage\Internal\FilesystemBridge;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Direct coverage of the Flysystem v1 ↔ v2/v3 version bridge. The directors
 * exercise it indirectly; this asserts the branch-selection itself plus the
 * constructor guards, so a regression in the version detection is caught at the
 * source rather than through a director failure three layers up.
 */
class FilesystemBridgeTest extends TestCase
{
    public function testIsLegacyMatchesTheInstalledFlysystemMajor()
    {
        $bridge = $this->makeInMemoryBridge();

        // v2/v3 expose FilesystemOperator; v1 only FilesystemInterface. The
        // bridge must report legacy=true precisely when the operator interface
        // is absent. This single assertion is true on every matrix cell and
        // proves the detected branch matches the resolved dependency.
        $expectedLegacy = !interface_exists('League\\Flysystem\\FilesystemOperator');
        $this->assertSame($expectedLegacy, $bridge->isLegacy());
    }

    public function testWrapsModernOperatorOnFlysystem2or3()
    {
        if (!interface_exists('League\\Flysystem\\FilesystemOperator')) {
            $this->markTestSkipped('Flysystem v1 installed; the legacy branch is active on this cell.');
        }

        $bridge = $this->makeInMemoryBridge();

        $this->assertFalse($bridge->isLegacy());
        $this->assertInstanceOf('League\\Flysystem\\FilesystemOperator', $bridge->getNativeFilesystem());
    }

    public function testWrapsLegacyInterfaceOnFlysystem1()
    {
        if (interface_exists('League\\Flysystem\\FilesystemOperator')) {
            $this->markTestSkipped('Flysystem v2/v3 installed; the modern branch is active on this cell.');
        }

        $bridge = $this->makeInMemoryBridge();

        $this->assertTrue($bridge->isLegacy());
        $this->assertInstanceOf('League\\Flysystem\\FilesystemInterface', $bridge->getNativeFilesystem());
    }

    public function testWriteReadRoundTripIsVersionAgnostic()
    {
        $bridge = $this->makeInMemoryBridge();

        $this->assertFalse($bridge->fileExists('notes/hello.txt'));

        $bridge->write('notes/hello.txt', 'bridge round-trip');

        $this->assertTrue($bridge->fileExists('notes/hello.txt'));
        $this->assertSame('bridge round-trip', $bridge->read('notes/hello.txt'));

        $bridge->delete('notes/hello.txt');

        $this->assertFalse($bridge->fileExists('notes/hello.txt'));
    }

    public function testConstructorRejectsNonObject()
    {
        $this->expectException(StorageException::class);

        new FilesystemBridge('not-a-filesystem');
    }

    public function testConstructorRejectsUnknownObjectShape()
    {
        $this->expectException(StorageException::class);

        new FilesystemBridge(new stdClass());
    }

    /**
     * Build an in-memory bridge against whichever Flysystem major Composer
     * resolved for the current cell (v2/v3 InMemoryFilesystemAdapter, else v1
     * MemoryAdapter). Mirrors the helper in AbstractDirectorTest.
     *
     * @return FilesystemBridge
     */
    private function makeInMemoryBridge()
    {
        if (class_exists('\\League\\Flysystem\\InMemory\\InMemoryFilesystemAdapter')) {
            $fs = new \League\Flysystem\Filesystem(new \League\Flysystem\InMemory\InMemoryFilesystemAdapter());
        } elseif (class_exists('\\League\\Flysystem\\Memory\\MemoryAdapter')) {
            $fs = new \League\Flysystem\Filesystem(new \League\Flysystem\Memory\MemoryAdapter());
        } else {
            // league/flysystem-memory covers all three majors in require-dev, so
            // this is an environment misconfiguration — fail loudly (matching
            // AbstractDirectorTest) rather than silently skipping real coverage.
            $this->fail('No Flysystem in-memory adapter available; check league/flysystem-memory install.');
        }

        return new FilesystemBridge($fs);
    }
}
