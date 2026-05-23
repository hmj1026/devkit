<?php

namespace Devkit\Logging\GoogleChat\Exception;

use RuntimeException;

/**
 * Raised by the GoogleChat log handler's constructor when no webhook URL
 * is supplied. The handler refuses to construct rather than swallow the
 * misconfiguration and silently drop records.
 *
 * Pure PHP — extends RuntimeException.
 */
class GoogleChatLogWebHookUrlNotSettingException extends RuntimeException
{
}
