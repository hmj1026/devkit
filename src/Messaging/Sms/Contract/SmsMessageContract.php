<?php

namespace Devkit\Messaging\Sms\Contract;

/**
 * Outbound SMS payload as seen by a driver's sendSms(). Carries the
 * recipient phone, body text, and an arbitrary options bag for
 * provider-specific extras (sender ID, scheduled send, country override,
 * etc.).
 *
 * Pure PHP — no Illuminate imports.
 */
interface SmsMessageContract
{
    /**
     * @return string  E.164-format recipient number (e.g. "+886912345678").
     */
    public function cellPhone();

    /**
     * @return string  Message body. Encoding (GSM-7 vs UCS-2) is the driver's concern.
     */
    public function body();

    /**
     * @return array  Provider-specific extras; empty array when none.
     */
    public function options();
}
