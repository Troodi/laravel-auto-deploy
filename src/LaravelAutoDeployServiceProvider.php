<?php

namespace Troodi\LaravelAutoDeploy;

use Illuminate\Support\ServiceProvider;

class LaravelAutoDeployServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Troodi\LaravelAutoDeploy\LaravelAutoDeployController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            LaravelAutoDeployCommand::class,
        ]);
        include __DIR__.'/routes.php';
    }
}
