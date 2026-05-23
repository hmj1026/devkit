<?php

namespace Devkit\Storage\Exception;

use RuntimeException;

/**
 * Raised when an upload fails the director's MIME or size validation.
 */
class FileFormatException extends RuntimeException
{
}
