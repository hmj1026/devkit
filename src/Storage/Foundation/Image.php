<?php

namespace Devkit\Storage\Foundation;

/**
 * File subtype that carries image-specific metadata. ImageDirector
 * populates width/height via getimagesize() before returning the instance.
 */
class Image extends File
{
    /** @var int */
    protected $width = 0;

    /** @var int */
    protected $height = 0;

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param  int  $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = (int) $width;

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param  int  $height
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = (int) $height;

        return $this;
    }
}
