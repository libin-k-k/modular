<?php

declare(strict_types=1);

namespace Modules\EnabledModule\Providers;

use Illuminate\Support\ServiceProvider;

class EnabledModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        config()->set('autoreg.enabled_module_loaded', 'yes');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'enabledmodule');
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'enabledmodule');
    }
}
