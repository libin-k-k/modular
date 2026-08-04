<?php

declare(strict_types=1);

namespace Libinkk\Modular\Providers;

use Illuminate\Support\ServiceProvider;
use Libinkk\Modular\Commands\CacheModulesCommand;
use Libinkk\Modular\Commands\ClearModulesCacheCommand;
use Libinkk\Modular\Commands\DeleteModuleCommand;
use Libinkk\Modular\Commands\DisableModuleCommand;
use Libinkk\Modular\Commands\EnableModuleCommand;
use Libinkk\Modular\Commands\ListModulesCommand;
use Libinkk\Modular\Commands\MakeModuleCommand;
use Libinkk\Modular\Commands\RenameModuleCommand;
use Libinkk\Modular\Support\ModuleRepository;
use Libinkk\Modular\Support\ModuleScaffolder;

class ModularServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/modular.php', 'modular');

        $this->app->singleton(ModuleRepository::class, function ($app): ModuleRepository {
            return new ModuleRepository(
                $app['files'],
                (string) config('modular.modules_path', base_path('Modules')),
                (string) config('modular.cache_file', base_path('bootstrap/cache/modular_modules.php'))
            );
        });

        $this->app->singleton(ModuleScaffolder::class, function ($app): ModuleScaffolder {
            return new ModuleScaffolder($app['files']);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/modular.php' => config_path('modular.php'),
        ], 'modular-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModuleCommand::class,
                ListModulesCommand::class,
                EnableModuleCommand::class,
                DisableModuleCommand::class,
                CacheModulesCommand::class,
                ClearModulesCacheCommand::class,
                RenameModuleCommand::class,
                DeleteModuleCommand::class,
            ]);
        }
    }
}
