<?php

namespace Devkit\Messaging\Sms;

use Devkit\Messaging\Sms\Contract\SmsResultContract;

/**
 * Concrete SMS dispatch outcome. Drivers (NullSmsDriver,
 * AbstractHttpSmsDriver subclasses) return one of these per sendSms()
 * call. Plain value object; no mutators.
 *
 * Pure PHP — no Illuminate imports.
 */
class SmsResult implements SmsResultContract
{
    /**
     * @var bool
     */
    protected $successful;

    /**
     * @var string|null
     */
    protected $messageId;

    /**
     * @var string|null
     */
    protected $statusCode;

    /**
     * @var mixed
     */
    protected $rawResponse;

    /**
     * @param  bool  $successful
     * @param  string|null  $messageId  Provider's tracking ID, null when none assigned.
     * @param  string|null  $statusCode  Provider's free-form status string.
     * @param  mixed  $rawResponse  Provider's raw payload (array / string / object).
     */
    public function __construct($successful, $messageId = null, $statusCode = null, $rawResponse = null)
    {
        $this->successful = (bool) $successful;
        $this->messageId = $messageId === null ? null : (string) $messageId;
        $this->statusCode = $statusCode === null ? null : (string) $statusCode;
        $this->rawResponse = $rawResponse;
    }

    public function isSuccessful()
    {
        return $this->successful;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }
}
