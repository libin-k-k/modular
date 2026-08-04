<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests;

use Libinkk\Modular\Providers\ModularServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
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
}
