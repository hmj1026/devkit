<?php

namespace Devkit\Laravel\Messaging\Sms;

use Devkit\Messaging\Sms\Contract\SmsMessageContract;
use Devkit\Messaging\Sms\SmsManager;
use Devkit\Messaging\Sms\SmsMessage;
use RuntimeException;

class SmsChannel
{
    /**
     * @var SmsManager
     */
    protected $manager;

    public function __construct(SmsManager $manager)
    {
        $this->manager = $manager;
    }

    public function send($notifiable, $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            throw new RuntimeException('SMS notification must define toSms($notifiable).');
        }

        $message = $notification->toSms($notifiable);

        if (is_string($message)) {
            $message = new SmsMessage($this->routeFor($notifiable), $message);
        } elseif (is_array($message)) {
            $message = new SmsMessage(
                isset($message['cell_phone']) ? $message['cell_phone'] : $this->routeFor($notifiable),
                isset($message['body']) ? $message['body'] : '',
                isset($message['options']) && is_array($message['options']) ? $message['options'] : array()
            );
        }

        if (!$message instanceof SmsMessageContract) {
            throw new RuntimeException('toSms($notifiable) must return a SmsMessageContract, string, or array.');
        }

        return $this->manager->driver()->sendSms($message);
    }

    protected function routeFor($notifiable)
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            return $notifiable->routeNotificationFor('sms');
        }

        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return $notifiable->routeNotificationForSms();
        }

        return null;
    }
}
