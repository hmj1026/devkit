<?php

namespace Devkit\Tests\Laravel\Storage;

use Devkit\Storage\Foundation\File;
use Devkit\Laravel\Storage\StorageAdapter;
use Devkit\Tests\Laravel\TestCase;
use Devkit\Tests\Storage\Uploader\Fixture\TestUpload;
use Illuminate\Support\Facades\Storage;

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
}
