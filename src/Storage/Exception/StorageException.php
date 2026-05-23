<?php

namespace Devkit\Storage\Exception;

use RuntimeException;

/**
 * Wraps any underlying Flysystem failure (v1 `FileNotFoundException`,
 * v3 `FilesystemException`, anything else) into a single stable
 * exception type devkit consumers can catch.
 */
class StorageException extends RuntimeException
{
}
