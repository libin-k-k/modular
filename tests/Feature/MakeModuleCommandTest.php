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
        $files->deleteDirectory(dirname($this->modulesPath) . DIRECTORY_SEPARATOR . 'bootstrap');
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
    }
}
