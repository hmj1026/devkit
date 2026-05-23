<?php

namespace Devkit\Messaging\Sms\Contract;

/**
 * SMS provider driver contract. Drivers MUST implement sendSms() returning
 * a SmsResultContract describing the dispatch outcome.
 *
 * Provider-specific configuration (API key, endpoint, sender ID) is
 * supplied via the driver's constructor; the manager
 * (Devkit\Messaging\Sms\SmsManager, Wave 4) handles registration.
 *
 * Pure PHP — no Illuminate imports.
 */
interface SmsDriverContract
{
    /**
     * Dispatch a single SMS message.
     *
     * @param  SmsMessageContract  $message
     * @return SmsResultContract
     */
    public function sendSms(SmsMessageContract $message);
}
