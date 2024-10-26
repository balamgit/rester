<?php

namespace Itsmg\Rester;

use Illuminate\Support\ServiceProvider;
use Itsmg\Rester\Console\Commands\ResterCli;

class ResterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ResterCli::class
            ]);
        }
    }

    public function register()
    {
    }
}
