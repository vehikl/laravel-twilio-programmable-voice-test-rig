<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class TestAppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
    }
}
