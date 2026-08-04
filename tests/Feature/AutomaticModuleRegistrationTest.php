<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Tests\TestCase;

class AutomaticModuleRegistrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('modular.modules_path', __DIR__ . '/../Fixtures/AutoRegister/Modules');
        $app['config']->set('modular.cache_file', __DIR__ . '/../Fixtures/AutoRegister/bootstrap/cache/modular_modules.php');
    }

    public function test_it_registers_enabled_module_provider_and_routes(): void
    {
        $this->assertSame('yes', config('autoreg.enabled_module_loaded'));
        $this->get('/enabled-module-ping')->assertOk()->assertSee('enabled');
    }

    public function test_it_skips_disabled_module_provider_and_routes(): void
    {
        $this->assertNull(config('autoreg.disabled_module_loaded'));
        $this->get('/disabled-module-ping')->assertNotFound();
    }
}
