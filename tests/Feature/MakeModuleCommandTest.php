<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    private string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = (string) config('modular.modules_path');
        $files = new Filesystem();
        $files->deleteDirectory($this->modulesPath);
        $files->ensureDirectoryExists($this->modulesPath);
    }

    public function test_it_creates_module_scaffold(): void
    {
        $this->artisan('modular:make', ['name' => 'User'])
            ->expectsOutputToContain('created successfully')
            ->assertSuccessful();

        $this->assertFileExists($this->modulesPath . '/User/module.json');
        $this->assertFileExists($this->modulesPath . '/User/Providers/UserServiceProvider.php');
        $this->assertFileExists($this->modulesPath . '/User/Routes/web.php');
        $this->assertFileExists($this->modulesPath . '/User/Routes/api.php');

        $providerContent = file_get_contents($this->modulesPath . '/User/Providers/UserServiceProvider.php') ?: '';
        $this->assertStringContainsString("\$this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');", $providerContent);
        $this->assertStringContainsString("\$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');", $providerContent);
        $this->assertStringContainsString("\$this->loadViewsFrom(__DIR__ . '/../Views', 'user');", $providerContent);
        $this->assertStringContainsString("\$this->loadTranslationsFrom(__DIR__ . '/../Lang', 'user');", $providerContent);
        $this->assertStringContainsString('mergeConfigFrom', $providerContent);
    }
}
