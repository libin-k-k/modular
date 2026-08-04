<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Tests\TestCase;

class RegistrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('modular.modules_path', __DIR__ . '/../Fixtures/AutoRegister/Modules');
        $app['config']->set('modular.cache_file', __DIR__ . '/../Fixtures/AutoRegister/bootstrap/cache/modular_modules.php');
    }

    public function test_enabled_module_is_registered_and_accessible(): void
    {
        $this->assertSame('yes', config('autoreg.enabled_module_loaded'));
        $this->assertTrue((bool) config('enabledmodule.settings.sample'));
        $this->get('/enabled-module-ping')->assertOk()->assertSee('enabled');
    }

    public function test_disabled_module_is_skipped_and_inaccessible(): void
    {
        $this->assertNull(config('autoreg.disabled_module_loaded'));
        $this->get('/disabled-module-ping')->assertNotFound();
    }
}
