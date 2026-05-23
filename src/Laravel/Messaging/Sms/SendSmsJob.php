<?php

namespace Devkit\Laravel\Messaging\Sms;

use Devkit\Messaging\Sms\SmsManager;
use Devkit\Messaging\Sms\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

    public function __construct($cellPhone, $body, array $options = array())
    {
        $this->cellPhone = (string) $cellPhone;
        $this->body = (string) $body;
        $this->options = $options;
    }

    public function handle(SmsManager $manager)
    {
        $driver = isset($this->options['driver']) ? $this->options['driver'] : null;
        $options = $this->options;
        unset($options['driver']);

        return $manager->driver($driver)->sendSms(new SmsMessage($this->cellPhone, $this->body, $options));
    }
}
