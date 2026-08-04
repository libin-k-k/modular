<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests;

use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Providers\ModularServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        $this->cleanupDefaultFixtures();
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ModularServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('modular.modules_path', __DIR__ . '/Fixtures/Modules');
        $app['config']->set('modular.cache_file', __DIR__ . '/Fixtures/bootstrap/cache/modular_modules.php');
    }

    private function cleanupDefaultFixtures(): void
    {
        $files = new Filesystem();
        $modulesPath = __DIR__ . '/Fixtures/Modules';
        $cacheDir = __DIR__ . '/Fixtures/bootstrap';

        if ($files->isDirectory($modulesPath)) {
            $files->deleteDirectory($modulesPath);
        }
        $files->ensureDirectoryExists($modulesPath);

        if ($files->isDirectory($cacheDir)) {
            $files->deleteDirectory($cacheDir);
        }
    }
}
