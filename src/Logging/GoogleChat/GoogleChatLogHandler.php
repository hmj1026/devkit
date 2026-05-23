<?php

/**
 * Dispatcher stub for the dual Monolog 2/3 GoogleChat handler.
 *
 * Composer PSR-4 autoloads this file when code references
 * `Devkit\Logging\GoogleChat\GoogleChatLogHandler`. The file does NOT
 * declare the class directly; instead it detects which Monolog major is
 * installed (via `class_exists('Monolog\\LogRecord')` — that class
 * exists only in Monolog 3.x) and requires the matching concrete from
 * Internal/, then exposes it under the canonical name via `class_alias`.
 *
 * Rationale: Monolog 3 changed AbstractProcessingHandler::write's
 * signature from `write(array $record): void` to
 * `write(LogRecord $record): void`. PHP's LSP forbids a single class
 * from satisfying both at once; we ship two concretes (one per
 * signature) and pick the right one at autoload time. The two
 * concretes share formatting + dispatch logic through the
 * HandlesGoogleChatCard trait.
 *
 * The class_alias guard prevents redefinition errors if some other code
 * path defined the alias first (e.g. user pre-loaded for testing).
 */

namespace Devkit\Logging\GoogleChat;

if (!class_exists(GoogleChatLogHandler::class, false)) {
    if (class_exists('Monolog\\LogRecord')) {
        require_once __DIR__ . '/Internal/GoogleChatLogHandlerM3.php';
        if (!class_exists(GoogleChatLogHandler::class, false)) {
            class_alias(
                Internal\GoogleChatLogHandlerM3::class,
                GoogleChatLogHandler::class
            );
        }
    } else {
        require_once __DIR__ . '/Internal/GoogleChatLogHandlerM2.php';
        if (!class_exists(GoogleChatLogHandler::class, false)) {
            class_alias(
                Internal\GoogleChatLogHandlerM2::class,
                GoogleChatLogHandler::class
            );
        }
    }
}
