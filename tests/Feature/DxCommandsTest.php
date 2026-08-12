<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Tests\Concerns\InteractsWithModules;
use Libinkk\Modular\Tests\TestCase;

class DxCommandsTest extends TestCase
{
    use InteractsWithModules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootModuleWorkspace();
        config(['modular.auto_refresh_cache' => true]);
    }

    public function test_make_api_web_empty_and_minimal_options(): void
    {
        $this->artisan('modular:make', ['name' => 'ApiOnly', '--api' => true])->assertSuccessful();
        $this->assertFileExists($this->modulePath('ApiOnly/Routes/api.php'));
        $this->assertFileDoesNotExist($this->modulePath('ApiOnly/Routes/web.php'));
        $this->assertFileExists($this->cacheFile);

        $this->artisan('modular:make', ['name' => 'WebOnly', '--web' => true])->assertSuccessful();
        $this->assertFileExists($this->modulePath('WebOnly/Routes/web.php'));
        $this->assertFileDoesNotExist($this->modulePath('WebOnly/Routes/api.php'));

        $this->artisan('modular:make', ['name' => 'Bare', '--empty' => true])->assertSuccessful();
        $this->assertFileExists($this->modulePath('Bare/module.json'));
        $this->assertFileDoesNotExist($this->modulePath('Bare/Providers/BareServiceProvider.php'));

        $this->artisan('modular:make', ['name' => 'Mini', '--minimal' => true])->assertSuccessful();
        $this->assertFileExists($this->modulePath('Mini/Providers/MiniServiceProvider.php'));
        $this->assertFileDoesNotExist($this->modulePath('Mini/Config/config.php'));
    }

    public function test_repository_creates_service_with_di_and_model_factory_hook(): void
    {
        $this->makeModule('Shop');
        $this->artisan('modular:repository', [
            'target' => 'Shop',
            'name' => 'ItemRepository',
            '--no-inject' => true,
        ])->assertSuccessful();

        $this->assertModuleFileContains(
            'Shop/Services/ItemService.php',
            'ItemRepositoryInterface $repository'
        );
        $this->artisan('modular:model', ['target' => 'Shop', 'name' => 'Item'])->assertSuccessful();
        $this->assertModuleFileContains(
            'Shop/Models/Item.php',
            'newFactory()',
            'Modules\\Shop\\Database\\Factories\\ItemFactory'
        );
    }

    public function test_crud_api_flag_and_info_status_doctor_suggestions(): void
    {
        $this->makeModule('Shop');
        $this->artisan('modular:crud', ['target' => 'Shop', 'name' => 'Product', '--api' => true])->assertSuccessful();
        $this->assertModuleFileContains('Shop/Controllers/ProductController.php', 'function index', 'paginate');
        $this->assertModuleFileContains('Shop/Services/ProductService.php', 'function paginate');
        $this->assertModuleFileContains('Shop/Repositories/ProductRepository.php', 'orderBy');

        $this->artisan('modular:info', ['name' => 'Shop'])
            ->expectsOutputToContain('Shop')
            ->assertSuccessful();

        $this->artisan('modular:status')
            ->expectsOutputToContain('Package Installed')
            ->assertSuccessful();

        $this->artisan('modular:enable', ['name' => 'Shopp'])->assertFailed();
        $this->artisan('modular:doctor')->assertSuccessful();
    }

    public function test_install_creates_modules_path_and_cache(): void
    {
        $this->files->deleteDirectory($this->modulesPath);
        $this->assertDirectoryDoesNotExist($this->modulesPath);

        $this->artisan('modular:install')->assertSuccessful();
        $this->assertDirectoryExists($this->modulesPath);
        $this->assertFileExists($this->cacheFile);
    }
}
