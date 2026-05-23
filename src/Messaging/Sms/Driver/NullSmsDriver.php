<?php

namespace Devkit\Messaging\Sms\Driver;

use Devkit\Messaging\Sms\Contract\SmsDriverContract;
use Devkit\Messaging\Sms\Contract\SmsMessageContract;
use Devkit\Messaging\Sms\SmsResult;

/**
 * Development / test driver. Records every dispatched message in an
 * in-memory log and returns a successful SmsResult; never touches the
 * network. Use it as the default driver in non-production environments
 * so test runs and local dev never accidentally hit a real provider.
 *
 * Pure PHP — no Illuminate imports.
 */
class NullSmsDriver implements SmsDriverContract
{
    /**
     * @var SmsMessageContract[]
     */
    protected $sent = array();

    /**
     * @var int
     */
    protected $idCounter = 0;

    public function sendSms(SmsMessageContract $message)
    {
        $this->sent[] = $message;
        $this->idCounter++;

        return new SmsResult(
            true,
            'null-' . $this->idCounter,
            'OK',
            array('recorded' => true)
        );
    }

    /**
     * Every message dispatched through this driver instance, in order.
     * Test code uses this to assert what was sent during the test run.
     *
     * @return SmsMessageContract[]
     */
    public function sentMessages()
    {
        return $this->sent;
    }

    /**
     * Drop the recorded log.
     *
     * @return $this
     */
    public function reset()
    {
        $this->sent = array();
        $this->idCounter = 0;

        return $this;
    }
}
