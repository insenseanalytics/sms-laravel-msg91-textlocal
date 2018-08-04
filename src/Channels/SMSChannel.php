<?php

namespace Insense\LaravelSMS\Channels;

use Illuminate\Notifications\Notification;

class SMSChannel {

    /**
     * The SMS driver client instance.
     *
     * @var \Insense\LaravelSMS\Channels\Contracts\SMSChannelDriver
     */
    protected $driver;

    public function __construct(SMSChannelManager $manager) {
        $this->driver = $manager->driver();
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification) {
        if (!$to = $notifiable->routeNotificationFor('SMS')) {
            return;
        }

        $message = $notification->toSMS($notifiable);

        if (is_string($message)) {
            $message = new SMSMessage($message);
        }

        if (is_array($message)) {
            $content = isset($message['content']) ? $message['content'] : "";
            $to = isset($message['to']) ? $message['to'] : null;
            $unicode = isset($message['unicode']) ? $message['unicode'] : false;
            $transactional = isset($message['transactional']) ? $message['transactional'] : true;
            $scheduleTime = isset($message['scheduleTime']) ? $message['scheduleTime'] : null;
            $message = new SMSMessage($content, $to, $unicode, $transactional, $scheduleTime);
        }

        return $this->driver->sendMessage($message);
    }

}
