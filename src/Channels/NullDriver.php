<?php

namespace InsenseSMS\Channels;

use InsenseSMS\Channels\Contracts\SMSChannelDriver;

class NullDriver implements SMSChannelDriver
{
    public function sendMessage(array $messageParams)
    {
        //
    }
}
