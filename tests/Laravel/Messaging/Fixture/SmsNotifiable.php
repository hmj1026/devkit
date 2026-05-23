<?php

namespace Devkit\Tests\Laravel\Messaging\Fixture;

use Illuminate\Notifications\Notifiable;

class SmsNotifiable
{
    use Notifiable;

    public function routeNotificationForSms()
    {
        return '+886912345678';
    }
}
