<?php

namespace Devkit\Search\Client\Exception;

use RuntimeException;

/**
 * Raised when ElasticsearchManager::connection() is called with a name
 * that has not been registered via extend().
 */
class ConnectionNotRegisteredException extends RuntimeException
{
}
