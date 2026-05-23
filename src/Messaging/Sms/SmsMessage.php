<?php

namespace Devkit\Messaging\Sms;

use Devkit\Messaging\Sms\Contract\SmsMessageContract;

/**
 * Concrete outbound SMS payload. Plain value object — getters only,
 * no fluent setters, so the message is effectively immutable once
 * constructed.
 *
 * Pure PHP — no Illuminate imports.
 */
class SmsMessage implements SmsMessageContract
{
    /**
     * @var string
     */
    protected $cellPhone;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param  string  $cellPhone  E.164-format recipient (e.g. "+886912345678").
     * @param  string  $body
     * @param  array  $options  Provider-specific extras (sender ID, scheduled send, etc.).
     */
    public function __construct($cellPhone, $body, array $options = array())
    {
        $this->cellPhone = (string) $cellPhone;
        $this->body = (string) $body;
        $this->options = $options;
    }

    public function cellPhone()
    {
        return $this->cellPhone;
    }

    public function body()
    {
        return $this->body;
    }

    public function options()
    {
        return $this->options;
    }
}
