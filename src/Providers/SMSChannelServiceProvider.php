<?php

namespace InsenseSMS\Providers;

use Illuminate\Support\ServiceProvider;
use InsenseSMS\Channels\SMSChannelManager;

class SMSChannelServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->app->singleton(SMSChannelManager::class, function ($app) {
            return new SMSChannelManager($app);
        });
        
        $this->app->singleton('sms', function ($app) {
            return $app->make(SMSChannelManager::class)->driver();
        });
    }

    public function boot()
    {
        $this->registerPublishables();
    }

    private function registerPublishables()
    {
        $basepath = dirname(__DIR__);
        $publishables = [
            'migrations' => [
                "$basepath/publishable/database/migrations" => \database_path("migrations")
            ],
            'config' => [
                "$basepath/publishable/config/sms.php" => \config_path("sms.php")
            ]

            ];
        foreach ($publishables as $group => $paths) {
            $this->publishes($paths, $group);
        }
    }
}
