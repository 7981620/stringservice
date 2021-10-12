<?php

namespace Agenta\StringService;

use Illuminate\Support\ServiceProvider;

class MyPackageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    /**
     *
     */
    public function register()
    {
        $this->registerMyPackage();
    }

    /**
     *
     */
    private function registerMyPackage(): void
    {
        $this->app->bind('StringService', function ($app) {
            return new StringService($app);
        });
    }
}
