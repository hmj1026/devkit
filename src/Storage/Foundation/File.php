<?php

namespace Devkit\Storage\Foundation;

use Devkit\Storage\Contract\FileContract;

/**
 * Represents a file written through the director pipeline. Holds the
 * per-disk path/URL maps so a single logical file replicated across
 * disks (e.g. local + s3) is addressable by either disk name.
 *
 * Pure PHP — no Illuminate imports.
 */
class File implements FileContract
{
    /** @var string|null */
    protected $id;

    /** @var string */
    protected $originalName = '';

    /** @var string */
    protected $storedName = '';

    /** @var string */
    protected $mimeType = '';

    /** @var int */
    protected $size = 0;

    /** @var string */
    protected $extension = '';

    /** @var string|null */
    protected $checksum;

    /** @var string */
    protected $visibility = 'public';

    /** @var string|null */
    protected $defaultDisk;

    /**
     * Per-disk storage path map: ['local' => 'ab/cd/file.jpg', 's3' => 'ab/cd/file.jpg'].
     *
     * @var array<string, string>
     */
    protected $paths = array();

    /**
     * Per-disk public URL map.
     *
     * @var array<string, string>
     */
    protected $urls = array();

    /**
     * @param  string|null  $disk  Null resolves to the default disk.
     * @return string  Empty string when the disk has no path recorded.
     */
    public function getPath($disk = null)
    {
        $key = $disk === null || $disk === '' ? $this->defaultDisk : (string) $disk;

        if ($key === null) {
            // No disk requested and no default set — fall back to the first
            // recorded path so callers still get a sensible answer.
            $first = reset($this->paths);

            return $first === false ? '' : (string) $first;
        }

        return isset($this->paths[$key]) ? $this->paths[$key] : '';
    }

    /**
     * @param  string|null  $disk
     * @return string
     */
    public function getUrl($disk = null)
    {
        $key = $disk === null || $disk === '' ? $this->defaultDisk : (string) $disk;

        if ($key === null) {
            $first = reset($this->urls);

            return $first === false ? '' : (string) $first;
        }

        return isset($this->urls[$key]) ? $this->urls[$key] : '';
    }

    /**
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * @return string
     */
    public function getStoredName()
    {
        return $this->storedName;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setOriginalName($name)
    {
        $this->originalName = (string) $name;

        return $this;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setStoredName($name)
    {
        $this->storedName = (string) $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param  string  $mime
     * @return $this
     */
    public function setMimeType($mime)
    {
        $this->mimeType = (string) $mime;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param  int  $bytes
     * @return $this
     */
    public function setSize($bytes)
    {
        $this->size = (int) $bytes;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param  string  $ext
     * @return $this
     */
    public function setExtension($ext)
    {
        $this->extension = (string) $ext;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getChecksum()
    {
        return $this->checksum;
    }

    /**
     * @param  string|null  $checksum
     * @return $this
     */
    public function setChecksum($checksum)
    {
        $this->checksum = $checksum === null ? null : (string) $checksum;

        return $this;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param  string  $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->visibility = (string) $visibility;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  string|null  $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id === null ? null : (string) $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDefaultDisk()
    {
        return $this->defaultDisk;
    }

    /**
     * @param  string|null  $disk
     * @return $this
     */
    public function setDefaultDisk($disk)
    {
        $this->defaultDisk = $disk === null ? null : (string) $disk;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * @param  string  $disk
     * @param  string  $path
     * @return $this
     */
    public function setPath($disk, $path)
    {
        $this->paths[(string) $disk] = (string) $path;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @param  string  $disk
     * @param  string  $url
     * @return $this
     */
    public function setUrl($disk, $url)
    {
        $this->urls[(string) $disk] = (string) $url;

        return $this;
    }
}
