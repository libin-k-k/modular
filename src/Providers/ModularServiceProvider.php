<?php

declare(strict_types=1);

namespace Libinkk\Modular\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Libinkk\Modular\Commands\CacheModulesCommand;
use Libinkk\Modular\Commands\ClearModulesCacheCommand;
use Libinkk\Modular\Commands\DeleteModuleCommand;
use Libinkk\Modular\Commands\DisableModuleCommand;
use Libinkk\Modular\Commands\EnableModuleCommand;
use Libinkk\Modular\Commands\ListModulesCommand;
use Libinkk\Modular\Commands\MakeActionCommand;
use Libinkk\Modular\Commands\MakeControllerCommand;
use Libinkk\Modular\Commands\MakeDtoCommand;
use Libinkk\Modular\Commands\MakeEnumCommand;
use Libinkk\Modular\Commands\MakeEventCommand;
use Libinkk\Modular\Commands\MakeJobCommand;
use Libinkk\Modular\Commands\MakeListenerCommand;
use Libinkk\Modular\Commands\MakeMiddlewareCommand;
use Libinkk\Modular\Commands\MakeModuleCommand;
use Libinkk\Modular\Commands\MakeModelCommand;
use Libinkk\Modular\Commands\MakeNotificationCommand;
use Libinkk\Modular\Commands\MakePolicyCommand;
use Libinkk\Modular\Commands\MakeRepositoryCommand;
use Libinkk\Modular\Commands\MakeRequestCommand;
use Libinkk\Modular\Commands\MakeResourceCommand;
use Libinkk\Modular\Commands\MakeRuleCommand;
use Libinkk\Modular\Commands\MakeServiceCommand;
use Libinkk\Modular\Commands\MakeTestCommand;
use Libinkk\Modular\Commands\RenameModuleCommand;
use Libinkk\Modular\Support\Module;
use Libinkk\Modular\Support\ModuleRepository;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

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
        $this->registerEnabledModules($this->app->make(ModuleRepository::class));

        $this->publishes([
            __DIR__ . '/../../config/modular.php' => config_path('modular.php'),
        ], 'modular-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModuleCommand::class,
                MakeControllerCommand::class,
                MakeModelCommand::class,
                MakeRequestCommand::class,
                MakeServiceCommand::class,
                MakeRepositoryCommand::class,
                MakeResourceCommand::class,
                MakeEventCommand::class,
                MakeListenerCommand::class,
                MakeJobCommand::class,
                MakeNotificationCommand::class,
                MakePolicyCommand::class,
                MakeMiddlewareCommand::class,
                MakeDtoCommand::class,
                MakeActionCommand::class,
                MakeEnumCommand::class,
                MakeRuleCommand::class,
                MakeTestCommand::class,
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

    private function registerEnabledModules(ModuleRepository $repository): void
    {
        $allModules = $repository->all();
        $enabledModules = array_values(array_filter($allModules, static fn (Module $module): bool => $module->enabled));
        $enabledNames = array_map(static fn (Module $module): string => $module->name, $enabledModules);

        foreach ($enabledModules as $module) {
            foreach ($module->dependencies as $dependency) {
                if (!in_array($dependency, $enabledNames, true)) {
                    throw new RuntimeException(sprintf(
                        'Module [%s] requires enabled dependency [%s].',
                        $module->name,
                        $dependency
                    ));
                }
            }
        }

        /** @var Filesystem $files */
        $files = $this->app['files'];

        foreach ($enabledModules as $module) {
            $this->registerModuleProvider($files, $module);
            $this->registerModuleResources($files, $module);
        }
    }

    private function registerModuleProvider(Filesystem $files, Module $module): void
    {
        $providerClass = sprintf('Modules\\%s\\Providers\\%sServiceProvider', $module->name, $module->name);
        $providerPath = $module->path . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $module->name . 'ServiceProvider.php';

        if ($files->exists($providerPath) && !class_exists($providerClass, false)) {
            require_once $providerPath;
        }

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    private function registerModuleResources(Filesystem $files, Module $module): void
    {
        $moduleLower = strtolower($module->name);

        $webRoutes = $module->path . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'web.php';
        $apiRoutes = $module->path . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'api.php';
        if ($files->exists($webRoutes)) {
            $this->loadRoutesFrom($webRoutes);
        }
        if ($files->exists($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }

        $migrationsPath = $module->path . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
        if ($files->isDirectory($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        $viewsPath = $module->path . DIRECTORY_SEPARATOR . 'Views';
        if ($files->isDirectory($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $moduleLower);
        }

        $langPath = $module->path . DIRECTORY_SEPARATOR . 'Lang';
        if ($files->isDirectory($langPath)) {
            $this->loadTranslationsFrom($langPath, $moduleLower);
        }

        $configPath = $module->path . DIRECTORY_SEPARATOR . 'Config';
        if ($files->isDirectory($configPath)) {
            foreach ($files->files($configPath) as $configFile) {
                if ($configFile->getExtension() !== 'php') {
                    continue;
                }

                $key = $moduleLower . '.' . $configFile->getBasename('.php');
                $this->mergeConfigFrom($configFile->getPathname(), $key);
            }
        }
    }
}
