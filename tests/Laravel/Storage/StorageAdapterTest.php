<?php

namespace Devkit\Tests\Laravel\Storage;

use Devkit\Storage\Foundation\File;
use Devkit\Storage\Uploader\FileDirector;
use Devkit\Storage\Uploader\ImageDirector;
use Devkit\Laravel\Storage\StorageAdapter;
use Devkit\Tests\Laravel\TestCase;
use Devkit\Tests\Storage\Uploader\Fixture\TestUpload;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class StorageAdapterTest extends TestCase
{
    public function testResolvesLaravelDiskForCoreDirector()
    {
        Storage::fake('uploads');

        $adapter = new StorageAdapter();
        $director = $adapter->director('file', array('uploads'), array(
            'allowMimeTypes' => array('text/plain'),
        ));

        $tmp = tempnam(sys_get_temp_dir(), 'devkit-laravel-upload-');
        file_put_contents($tmp, 'stored by laravel adapter');

        $file = $director->upload(new TestUpload($tmp, 'note.txt', 'text/plain'));

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('stored by laravel adapter', $adapter->read('uploads', $file->getPath('uploads')));

        @unlink($tmp);
    }

    public function testDefaultDirectorTypeIsFileDirector()
    {
        Storage::fake('uploads');

        $adapter = new StorageAdapter();

        $this->assertInstanceOf(FileDirector::class, $adapter->director('file', array('uploads')));
    }

    public function testImageTypeReturnsImageDirector()
    {
        Storage::fake('uploads');

        $adapter = new StorageAdapter();

        $this->assertInstanceOf(ImageDirector::class, $adapter->director('image', array('uploads')));
    }

    public function testUnknownDirectorTypeThrows()
    {
        Storage::fake('uploads');

        $adapter = new StorageAdapter();

        $this->expectException(InvalidArgumentException::class);
        $adapter->director('bogus', array('uploads'));
    }

    public function testDiskResolvesAnUnderlyingFilesystem()
    {
        Storage::fake('uploads');

        $adapter = new StorageAdapter();

        // disk() unwraps to the underlying Flysystem driver/operator (or the
        // adapter itself on builds without getDriver()); either way an object.
        $this->assertIsObject($adapter->disk('uploads'));
    }

    public function testReadReturnsStoredContents()
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('docs/readme.txt', 'direct read path');

        $adapter = new StorageAdapter();

        $this->assertSame('direct read path', $adapter->read('uploads', 'docs/readme.txt'));
    }
}
