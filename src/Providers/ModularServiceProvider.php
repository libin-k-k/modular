<?php

declare(strict_types=1);

namespace Libinkk\Modular\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Libinkk\Modular\Commands\CommandRegistry;
use Libinkk\Modular\Providers\Concerns\RegistersEnabledModules;
use Libinkk\Modular\Support\ModuleRenamer;
use Libinkk\Modular\Support\ModuleRepository;
use Libinkk\Modular\Support\ModuleScaffolder;

class ModularServiceProvider extends ServiceProvider
{
    use RegistersEnabledModules;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/modular.php', 'modular');

        $this->app->singleton(ModuleRenamer::class, function ($app): ModuleRenamer {
            return new ModuleRenamer($app->make(Filesystem::class));
        });

        $this->app->singleton(ModuleRepository::class, function ($app): ModuleRepository {
            return new ModuleRepository(
                $app->make(Filesystem::class),
                (string) config('modular.modules_path', base_path('Modules')),
                (string) config('modular.cache_file', base_path('bootstrap/cache/modular_modules.php')),
                $app->make(ModuleRenamer::class)
            );
        });

        $this->app->singleton(ModuleScaffolder::class, function ($app): ModuleScaffolder {
            return new ModuleScaffolder($app->make(Filesystem::class));
        });
    }

    public function boot(): void
    {
        $this->registerEnabledModules(
            $this->app->make(ModuleRepository::class),
            (bool) config('modular.prefer_cache', true)
        );

        $this->publishes([
            __DIR__ . '/../../config/modular.php' => config_path('modular.php'),
        ], 'modular-config');

        if ($this->app->runningInConsole()) {
            $this->commands(CommandRegistry::all());
        }
    }
}
