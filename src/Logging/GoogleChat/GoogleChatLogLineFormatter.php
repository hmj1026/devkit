<?php

namespace Devkit\Logging\GoogleChat;

use Monolog\Formatter\LineFormatter;

/**
 * Thin extension of Monolog's LineFormatter for GoogleChat-bound records.
 *
 * Monolog 2 and 3 both ship Monolog\Formatter\LineFormatter with a
 * stable public surface (constructor accepts format / dateFormat /
 * allowInlineLineBreaks / ignoreEmptyContextAndExtra); we reuse it as-is
 * but pre-configure with a GoogleChat-friendly default format and inline
 * line-breaks enabled (cards render \n as soft breaks).
 *
 * Used by the Laravel custom-log-driver adapter when assembling
 * the handler; consumers calling the handler directly typically supply
 * their own formatter or let Monolog default kick in.
 */
class GoogleChatLogLineFormatter extends LineFormatter
{
    /**
     * @param  string|null  $format  Falls back to a GoogleChat-friendly default.
     * @param  string|null  $dateFormat  Defaults to LineFormatter's default ('Y-m-d\TH:i:sP').
     * @param  bool  $allowInlineLineBreaks  Default true so multi-line messages render in cards.
     * @param  bool  $ignoreEmptyContextAndExtra  Default true so empty arrays don't clutter the card.
     */
    public function __construct(
        $format = null,
        $dateFormat = null,
        $allowInlineLineBreaks = true,
        $ignoreEmptyContextAndExtra = true
    ) {
        if ($format === null) {
            $format = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        }
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }
}
