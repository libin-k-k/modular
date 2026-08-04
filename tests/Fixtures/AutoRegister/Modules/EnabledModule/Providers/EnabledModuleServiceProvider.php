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
    }
}
