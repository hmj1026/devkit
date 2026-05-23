<?php

namespace Devkit\Laravel\Storage;

use Devkit\Storage\Enum\PathMethodEnum;
use Devkit\Storage\Uploader\FileDirector;
use Devkit\Storage\Uploader\ImageDirector;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class StorageAdapter
{
    public function disk($name)
    {
        $disk = Storage::disk($name);

        if (method_exists($disk, 'getDriver')) {
            return $disk->getDriver();
        }

        return $disk;
    }

    public function director($type = 'file', array $disks = array('local'), array $options = array())
    {
        $resolved = array();

        foreach ($disks as $disk) {
            $resolved[$disk] = $this->disk($disk);
        }

        $pathMethod = isset($options['pathMethod']) ? $options['pathMethod'] : PathMethodEnum::MD5;
        unset($options['pathMethod']);

        if ($type === 'image') {
            return new ImageDirector($resolved, $pathMethod, $options);
        }

        if ($type === 'file') {
            return new FileDirector($resolved, $pathMethod, $options);
        }

        throw new InvalidArgumentException('Unknown upload director [' . $type . '].');
    }

    public function read($disk, $path)
    {
        return Storage::disk($disk)->get($path);
    }
}
