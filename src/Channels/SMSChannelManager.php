<?php

namespace Insense\LaravelSMS\Channels;

use Illuminate\Support\Manager;

class SMSChannelManager extends Manager {

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver() {
        return $this->app['config']['sms.driver'];
    }

    public function createTextlocalDriver() {
        $config = $this->app['config']['sms.textlocal'];
        return new Textlocal($config['apiKey'], $config['endpoint'], $config['username'], $config['hash'], $config['trans_sender'], $config['promo_sender']);
    }

    public function createMsg91Driver() {
        $config = $this->app['config']['sms.msg91'];
        return new Msg91($config['apiKey'], $config['endpoint'], $config['trans_sender'], $config['promo_sender']);
    }

    public function createNullDriver() {
        return new NullDriver;
    }

}
