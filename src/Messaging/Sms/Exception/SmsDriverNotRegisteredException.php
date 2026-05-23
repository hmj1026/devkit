<?php

namespace Devkit\Messaging\Sms\Exception;

use RuntimeException;

/**
 * Raised by SmsManager::driver() when callers request a driver name
 * that was never registered via extend() (or auto-registered at boot).
 */
class SmsDriverNotRegisteredException extends RuntimeException
{
}
