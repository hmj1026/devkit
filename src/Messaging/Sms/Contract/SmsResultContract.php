<?php

namespace Devkit\Messaging\Sms\Contract;

/**
 * Outcome of sending an SMS through a driver. Drivers MUST report success
 * vs failure plus enough provider data for consumers to correlate the
 * dispatch with later delivery-status callbacks.
 *
 * Pure PHP — no Illuminate imports.
 */
interface SmsResultContract
{
    /**
     * @return bool  True when the provider accepted the message for delivery.
     */
    public function isSuccessful();

    /**
     * @return string|null  Provider's message id (for delivery tracking), or null when not assigned.
     */
    public function getMessageId();

    /**
     * @return string|null  Provider status code (free-form), or null.
     */
    public function getStatusCode();

    /**
     * @return mixed  Raw response from the provider (array / string / object) for audit.
     */
    public function getRawResponse();
}
