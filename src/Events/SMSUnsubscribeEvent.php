<?php

namespace Insense\LaravelSMS\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class SMSUnsubscribeEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $reports;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($reports)
    {
        $this->reports = $reports;
    }
}
