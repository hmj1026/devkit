<?php

namespace Devkit\Storage\Uploader;

use Devkit\Storage\Foundation\File;
use Devkit\Storage\Foundation\Image;

/**
 * Director for image uploads. Overrides createFile() to instantiate
 * Image and populate width/height via getimagesize() when a local
 * temp path is available; falls back to a zero-dim Image otherwise.
 */
class ImageDirector extends AbstractDirector
{
    /**
     * {@inheritdoc}
     *
     * @return File
     */
    protected function createFile($originalName, $storedName, $path, $size, $mime, $tmpPath = null)
    {
        $image = new Image();
        $image->setOriginalName($originalName);
        $image->setStoredName($storedName);
        $image->setSize($size);
        $image->setMimeType($mime);

        if ($tmpPath !== null && $tmpPath !== '' && is_readable($tmpPath)) {
            $info = @getimagesize($tmpPath);
            if (is_array($info)) {
                $image->setWidth(isset($info[0]) ? (int) $info[0] : 0);
                $image->setHeight(isset($info[1]) ? (int) $info[1] : 0);
            }
        }

        return $image;
    }
}
