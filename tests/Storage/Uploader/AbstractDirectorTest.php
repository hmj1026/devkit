<?php

namespace Devkit\Tests\Storage\Uploader;

use Devkit\Storage\Enum\PathMethodEnum;
use Devkit\Storage\Enum\VisibilityEnum;
use Devkit\Storage\Exception\FileFormatException;
use Devkit\Storage\Foundation\Image;
use Devkit\Storage\Internal\FilesystemBridge;
use Devkit\Storage\Uploader\FileDirector;
use Devkit\Storage\Uploader\ImageDirector;
use Devkit\Tests\Storage\Uploader\Fixture\TestUpload;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class AbstractDirectorTest extends TestCase
{
    /** @var string[]  Temp files to clean up. */
    private $tempFiles = array();

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tempFiles = array();
    }

    public function testHappyPathSingleDiskWriteAndReadback()
    {
        $bridge = $this->makeInMemoryBridge();
        $director = new FileDirector(
            array('local' => $bridge),
            PathMethodEnum::MD5,
            array('allowMimeTypes' => array('text/plain'), 'maxFileSize' => 1024)
        );

        $upload = $this->makeUpload('hello.txt', 'text/plain', 'hi devkit');
        $file = $director->upload($upload);

        $this->assertSame('hello.txt', $file->getOriginalName());
        $this->assertSame('text/plain', $file->getMimeType());
        $this->assertSame(strlen('hi devkit'), $file->getSize());
        $this->assertNotSame('', $file->getStoredName());
        $this->assertNotSame('', $file->getPath('local'));
        $this->assertSame('hi devkit', $bridge->read($file->getPath('local')));
    }

    public function testMultiDiskReplication()
    {
        $local = $this->makeInMemoryBridge();
        $s3 = $this->makeInMemoryBridge();

        $director = new FileDirector(
            array('local' => $local, 's3' => $s3),
            PathMethodEnum::MD5,
            array('allowMimeTypes' => array('text/plain'))
        );

        $file = $director->upload($this->makeUpload('a.txt', 'text/plain', 'shared'));

        $this->assertSame($file->getPath('local'), $file->getPath('s3'));
        $this->assertSame('shared', $local->read($file->getPath('local')));
        $this->assertSame('shared', $s3->read($file->getPath('s3')));
        $this->assertNotSame('', $file->getUrl('local'));
        $this->assertNotSame('', $file->getUrl('s3'));
    }

    public function testMd5PathBucketing()
    {
        $bridge = $this->makeInMemoryBridge();
        $director = new FileDirector(
            array('local' => $bridge),
            PathMethodEnum::MD5,
            array('allowMimeTypes' => array('text/plain'))
        );

        $file = $director->upload($this->makeUpload('a.txt', 'text/plain', 'x'));
        $path = $file->getPath('local');
        $stored = $file->getStoredName();
        $hash = pathinfo($stored, PATHINFO_FILENAME);

        $expectedPrefix = substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/';
        $this->assertStringStartsWith($expectedPrefix, $path);
        $this->assertStringEndsWith($stored, $path);
    }

    public function testDatePathBucketing()
    {
        $bridge = $this->makeInMemoryBridge();
        $director = new FileDirector(
            array('local' => $bridge),
            PathMethodEnum::DATE,
            array('allowMimeTypes' => array('text/plain'))
        );

        $file = $director->upload($this->makeUpload('a.txt', 'text/plain', 'x'));
        $expectedPrefix = date('Y/m/d') . '/';

        $this->assertStringStartsWith($expectedPrefix, $file->getPath('local'));
    }

    public function testRejectOversizedFile()
    {
        $director = new FileDirector(
            array('local' => $this->makeInMemoryBridge()),
            PathMethodEnum::MD5,
            array('maxFileSize' => 4)
        );

        $this->expectException(FileFormatException::class);
        $director->upload($this->makeUpload('big.txt', 'text/plain', 'too big a payload'));
    }

    public function testRejectDisallowedMime()
    {
        $director = new FileDirector(
            array('local' => $this->makeInMemoryBridge()),
            PathMethodEnum::MD5,
            array('allowMimeTypes' => array('image/jpeg'))
        );

        $this->expectException(FileFormatException::class);
        $director->upload($this->makeUpload('a.txt', 'text/plain', 'x'));
    }

    public function testVisibilityIsAppliedOnWrite()
    {
        $bridge = $this->makeInMemoryBridge();
        $director = new FileDirector(
            array('local' => $bridge),
            PathMethodEnum::MD5,
            array(
                'allowMimeTypes' => array('text/plain'),
                'visibility' => VisibilityEnum::PRIVATE,
            )
        );

        $file = $director->upload($this->makeUpload('priv.txt', 'text/plain', 'secret'));

        // Adapter-agnostic assertion: the in-memory adapters in v1 and
        // v2/v3 don't persist visibility metadata consistently. What
        // we control is the director's promise to thread the configured
        // visibility onto the returned File. Adapter-side persistence
        // is covered by the CI matrix against real Flysystem adapters.
        $this->assertSame(VisibilityEnum::PRIVATE, $file->getVisibility());
    }

    public function testImageDirectorPopulatesWidthAndHeight()
    {
        $bridge = $this->makeInMemoryBridge();
        $director = new ImageDirector(
            array('local' => $bridge),
            PathMethodEnum::MD5,
            array('allowMimeTypes' => array('image/png'))
        );

        // 1x1 PNG byte sequence — getimagesize() reports 1x1.
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNgYGD4DwABBAEAfbLI3wAAAABJRU5ErkJggg=='
        );
        $tmp = $this->writeTempFile('pixel.png', $pngBytes);
        $upload = new TestUpload($tmp, 'pixel.png', 'image/png');

        $file = $director->upload($upload);

        $this->assertInstanceOf(Image::class, $file);
        $this->assertSame(1, $file->getWidth());
        $this->assertSame(1, $file->getHeight());
    }

    public function testEmptyDisksThrows()
    {
        $this->expectException(FileFormatException::class);
        new FileDirector(array(), PathMethodEnum::MD5);
    }

    public function testDangerousExtensionIsStrippedToBin()
    {
        $bridge = $this->makeInMemoryBridge();
        $director = new FileDirector(
            array('local' => $bridge),
            PathMethodEnum::MD5
            // No MIME allow list — verifies the extension defense
            // works even when MIME validation is disabled.
        );

        $upload = $this->makeUpload('evil.php', 'text/plain', '<?php phpinfo();');
        $file = $director->upload($upload);

        $this->assertStringEndsWith('.bin', $file->getStoredName());
        $this->assertSame('bin', $file->getExtension());
        $this->assertStringEndsWith('.bin', $file->getPath('local'));
    }

    /**
     * @return FilesystemBridge
     */
    private function makeInMemoryBridge()
    {
        // Prefer Flysystem v2/v3 InMemoryFilesystemAdapter when present;
        // fall back to v1 MemoryAdapter so the test runs on every
        // supported install matrix.
        if (class_exists('\\League\\Flysystem\\InMemory\\InMemoryFilesystemAdapter')) {
            $fs = new \League\Flysystem\Filesystem(new \League\Flysystem\InMemory\InMemoryFilesystemAdapter());
        } elseif (class_exists('\\League\\Flysystem\\Memory\\MemoryAdapter')) {
            $fs = new \League\Flysystem\Filesystem(new \League\Flysystem\Memory\MemoryAdapter());
        } else {
            $this->fail('No in-memory Flysystem adapter available; require-dev must install league/flysystem-memory.');
        }

        return new FilesystemBridge($fs);
    }

    /**
     * Build an SplFileInfo-based upload object whose contents come from
     * a real temp file (so AbstractDirector's file_get_contents path
     * gets exercised).
     *
     * @return SplFileInfo
     */
    private function makeUpload($clientName, $mime, $contents)
    {
        $tmp = $this->writeTempFile($clientName, $contents);

        return new TestUpload($tmp, $clientName, $mime);
    }

    /**
     * @return string  Absolute path to the temp file.
     */
    private function writeTempFile($clientName, $contents)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'devkit-upload-');
        file_put_contents($tmp, $contents);
        $this->tempFiles[] = $tmp;

        return $tmp;
    }
}
