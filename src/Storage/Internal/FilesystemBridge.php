<?php

namespace Devkit\Storage\Internal;

use Devkit\Storage\Enum\VisibilityEnum;
use Devkit\Storage\Exception\StorageException;
use Throwable;

/**
 * Normalises the API differences between Flysystem v1
 * (`League\Flysystem\FilesystemInterface`, write returns bool) and v2/v3
 * (`League\Flysystem\FilesystemOperator`, write returns void and throws
 * `FilesystemException`). Devkit callers only need to deal with this
 * one shape; the version detection happens once at construction.
 *
 * Pure PHP — no Illuminate imports.
 *
 * @internal Not part of the public API.
 */
class FilesystemBridge
{
    /** @var object */
    private $filesystem;

    /** @var bool True for v1 (FilesystemInterface), false for v2/v3 operators. */
    private $legacy;

    /**
     * @param  object  $filesystem  League\Flysystem\FilesystemOperator (v2/v3) or
     *                              League\Flysystem\FilesystemInterface (v1).
     *
     * @throws StorageException  When the argument is neither shape.
     */
    public function __construct($filesystem)
    {
        if (!is_object($filesystem)) {
            throw new StorageException('FilesystemBridge requires a Flysystem instance.');
        }

        $operatorInterface = 'League\\Flysystem\\FilesystemOperator';
        $legacyInterface = 'League\\Flysystem\\FilesystemInterface';

        if (interface_exists($operatorInterface) && $filesystem instanceof $operatorInterface) {
            $this->legacy = false;
        } elseif (interface_exists($legacyInterface) && $filesystem instanceof $legacyInterface) {
            $this->legacy = true;
        } else {
            throw new StorageException(
                'FilesystemBridge expects a League\\Flysystem\\FilesystemOperator (v2/v3) '
                . 'or League\\Flysystem\\FilesystemInterface (v1); got '
                . get_class($filesystem) . '.'
            );
        }

        $this->filesystem = $filesystem;
    }

    /**
     * Write a file. Visibility is mapped through {@see VisibilityEnum}
     * so callers can pass our canonical strings ('public' / 'private')
     * regardless of the underlying Flysystem version.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  array  $config  May include a `visibility` key.
     * @return void
     *
     * @throws StorageException
     */
    public function write($path, $contents, array $config = array())
    {
        if (isset($config['visibility'])) {
            $config['visibility'] = VisibilityEnum::toFlysystemValue($config['visibility']);
        }

        try {
            if ($this->legacy) {
                // v1: writes return bool; false → throw.
                $ok = $this->filesystem->put($path, $contents, $config);
                if ($ok === false) {
                    throw new StorageException('Flysystem v1 write returned false for path: ' . $path);
                }
            } else {
                // v2/v3: void return; throws on failure.
                $this->filesystem->write($path, $contents, $config);
            }
        } catch (StorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new StorageException(
                'Filesystem write failed for path [' . $path . ']: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * @param  string  $path
     * @return string
     *
     * @throws StorageException
     */
    public function read($path)
    {
        try {
            $contents = $this->filesystem->read($path);
            // v1 returns false on miss; v2/v3 throw.
            if ($contents === false) {
                throw new StorageException('Flysystem v1 read returned false for path: ' . $path);
            }

            return (string) $contents;
        } catch (StorageException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new StorageException(
                'Filesystem read failed for path [' . $path . ']: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * @param  string  $path
     * @return void
     *
     * @throws StorageException
     */
    public function delete($path)
    {
        try {
            $this->filesystem->delete($path);
        } catch (Throwable $e) {
            throw new StorageException(
                'Filesystem delete failed for path [' . $path . ']: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * @param  string  $path
     * @return bool
     */
    public function fileExists($path)
    {
        try {
            if ($this->legacy) {
                return (bool) $this->filesystem->has($path);
            }

            // v2/v3 split has() into fileExists/directoryExists; prefer fileExists.
            if (method_exists($this->filesystem, 'fileExists')) {
                return (bool) $this->filesystem->fileExists($path);
            }

            return (bool) $this->filesystem->has($path);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Set visibility on an existing path. Accepts devkit canonical
     * strings; mapping to the underlying Flysystem value happens here.
     *
     * @param  string  $path
     * @param  string  $visibility
     * @return void
     *
     * @throws StorageException
     */
    public function setVisibility($path, $visibility)
    {
        $value = VisibilityEnum::toFlysystemValue($visibility);

        try {
            $this->filesystem->setVisibility($path, $value);
        } catch (Throwable $e) {
            throw new StorageException(
                'Filesystem setVisibility failed for path [' . $path . ']: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Return the canonical devkit visibility string for the path.
     *
     * @param  string  $path
     * @return string
     */
    public function getVisibility($path)
    {
        try {
            // Branch on version explicitly so a real failure on v2/v3's
            // visibility() doesn't get masked by a fall-through to a v1
            // method that doesn't exist.
            $raw = $this->legacy
                ? $this->filesystem->getVisibility($path)
                : $this->filesystem->visibility($path);
        } catch (Throwable $e) {
            throw new StorageException(
                'Filesystem visibility lookup failed for path [' . $path . ']: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return VisibilityEnum::fromFlysystemValue($raw);
    }

    /**
     * @return object  The wrapped Flysystem instance (for adapters that
     *                 need to access version-specific APIs directly).
     */
    public function getNativeFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @return bool  True when wrapping a v1 FilesystemInterface.
     */
    public function isLegacy()
    {
        return $this->legacy;
    }
}
