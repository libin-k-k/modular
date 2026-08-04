<?php

declare(strict_types=1);

namespace Libinkk\Modular\Providers\Concerns;

use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Support\Module;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

trait RegistersEnabledModules
{
    protected function registerEnabledModules(ModuleRepository $repository, bool $preferCache = true): void
    {
        $useCache = $preferCache && $repository->hasCache();
        $enabledModules = $repository->enabled($useCache);
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

        $files = $this->app->make(Filesystem::class);

        foreach ($enabledModules as $module) {
            $this->registerModuleProvider($files, $module);
            $this->registerModuleViewsLangConfig($files, $module);
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

    private function registerModuleViewsLangConfig(Filesystem $files, Module $module): void
    {
        $moduleLower = strtolower($module->name);

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
