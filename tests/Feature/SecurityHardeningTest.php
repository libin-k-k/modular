<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Support\ModuleRepository;
use Libinkk\Modular\Tests\Concerns\InteractsWithModules;
use Libinkk\Modular\Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use InteractsWithModules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootModuleWorkspace();
    }

    public function test_make_rejects_path_traversal_module_names(): void
    {
        $this->artisan('modular:make', ['name' => '../Evil'])->assertFailed();
        $this->artisan('modular:make', ['name' => 'Foo/Bar'])->assertFailed();
        $this->assertDirectoryDoesNotExist($this->modulePath('../Evil'));
        $this->assertDirectoryDoesNotExist(dirname($this->modulesPath) . '/Evil');
    }

    public function test_generators_reject_traversal_artifact_names(): void
    {
        $this->makeModule('Shop');

        $this->artisan('modular:controller', [
            'target' => 'Shop',
            'name' => '../EvilController',
            '--no-inject' => true,
        ])->assertFailed();

        $this->artisan('modular:view', [
            'target' => '../../evil',
            '--module' => 'Shop',
        ])->assertFailed();

        $this->assertFileDoesNotExist($this->modulePath('Shop/Controllers/EvilController.php'));
    }

    public function test_rename_and_delete_reject_invalid_names_and_stay_in_modules_root(): void
    {
        $this->makeModule('Safe');

        $this->artisan('modular:rename', ['from' => 'Safe', 'to' => '../Hack'])->assertFailed();
        $this->assertDirectoryExists($this->modulePath('Safe'));
        $this->assertDirectoryDoesNotExist(dirname($this->modulesPath) . '/Hack');

        $this->artisan('modular:delete', ['name' => '../Safe', '--force' => true])->assertFailed();
        $this->assertDirectoryExists($this->modulePath('Safe'));

        /** @var ModuleRepository $repo */
        $repo = $this->app->make(ModuleRepository::class);
        $repo->delete('Safe');
        $this->assertDirectoryDoesNotExist($this->modulePath('Safe'));
    }

    public function test_crud_rejects_invalid_resource_names(): void
    {
        $this->makeModule('Catalog');

        $this->artisan('modular:crud', [
            'target' => 'Catalog',
            'name' => '../Product',
        ])->assertFailed();
    }
}
