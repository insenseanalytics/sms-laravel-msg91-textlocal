<?php

namespace Insense\LaravelSMS\Channels;

use Insense\LaravelSMS\Channels\Contracts\SMSChannelDriver;

class NullDriver implements SMSChannelDriver
{
    public function sendMessage(array $messageParams)
    {
        //
    }
}
