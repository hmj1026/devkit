<?php

namespace Devkit\Tests\Laravel\Messaging\Fixture;

use Devkit\Messaging\Sms\SmsMessage;
use Devkit\Laravel\Messaging\Sms\SmsChannel;
use Illuminate\Notifications\Notification;

class SmsNotification extends Notification
{
    public function via($notifiable)
    {
        return array(SmsChannel::class);
    }

    public function toSms($notifiable)
    {
        return new SmsMessage($notifiable->routeNotificationFor('sms'), 'hello from notification');
    }
}
