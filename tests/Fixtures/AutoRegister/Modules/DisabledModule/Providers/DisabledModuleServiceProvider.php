<?php

declare(strict_types=1);

namespace Modules\DisabledModule\Providers;

use Illuminate\Support\ServiceProvider;

class DisabledModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        config()->set('autoreg.disabled_module_loaded', 'yes');
    }
}
