<?php

namespace Jiaxincui\Throttler;

use Illuminate\Support\ServiceProvider;

class ThrottlerServiceProvider extends ServiceProvider
{

    /**
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/throttler.php' => config_path('throttler.php')
        ]);
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ThrottlerManager::class, function ($app) {
            return new ThrottlerManager($app);
        });
    }
}
