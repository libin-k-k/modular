<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Support\ModuleRepository;
use Libinkk\Modular\Tests\Concerns\InteractsWithModules;
use Libinkk\Modular\Tests\TestCase;

/**
 * High-level smoke / end-to-end coverage for the package's main flows.
 */
class HighLevelWorkflowTest extends TestCase
{
    use InteractsWithModules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootModuleWorkspace();
    }

    public function test_full_module_lifecycle_from_create_to_delete(): void
    {
        $this->artisan('modular:make', ['name' => 'Billing'])->assertSuccessful();

        $this->assertFileExists($this->modulePath('Billing/module.json'));
        $this->assertFileExists($this->modulePath('Billing/Providers/BillingServiceProvider.php'));

        $this->artisan('modular:crud', [
            'target' => 'Invoice',
            '--module' => 'Billing',
        ])->assertSuccessful();

        foreach ([
            'Models/Invoice.php',
            'Controllers/InvoiceController.php',
            'Services/InvoiceService.php',
            'Repositories/InvoiceRepository.php',
            'Interfaces/InvoiceRepositoryInterface.php',
        ] as $relative) {
            $this->assertFileExists($this->modulePath("Billing/{$relative}"));
        }

        $this->artisan('modular:list')->expectsOutputToContain('Billing')->assertSuccessful();
        $this->artisan('modular:info', ['name' => 'Billing'])->expectsOutputToContain('Billing')->assertSuccessful();
        $this->artisan('modular:status')->expectsOutputToContain('Package Installed')->assertSuccessful();

        $this->artisan('modular:disable', ['name' => 'Billing'])->assertSuccessful();
        $manifest = json_decode($this->files->get($this->modulePath('Billing/module.json')), true);
        $this->assertFalse((bool) ($manifest['enabled'] ?? true));

        $this->artisan('modular:enable', ['name' => 'Billing'])->assertSuccessful();
        $manifest = json_decode($this->files->get($this->modulePath('Billing/module.json')), true);
        $this->assertTrue((bool) ($manifest['enabled'] ?? false));

        $this->artisan('modular:cache')->assertSuccessful();
        $this->assertFileExists($this->cacheFile);
        $this->artisan('modular:list', ['--cached' => true])->expectsOutputToContain('Billing')->assertSuccessful();

        $this->artisan('modular:doctor')->expectsOutputToContain('Doctor check passed.')->assertSuccessful();

        $this->artisan('modular:delete', ['name' => 'Billing', '--force' => true])->assertSuccessful();
        $this->assertDirectoryDoesNotExist($this->modulePath('Billing'));
    }

    public function test_multi_module_discovery_sorting_and_enabled_filter(): void
    {
        $this->makeModule('Zebra');
        $this->makeModule('Alpha');
        $this->makeModule('Middle');

        $this->artisan('modular:disable', ['name' => 'Middle'])->assertSuccessful();

        /** @var ModuleRepository $repo */
        $repo = $this->app->make(ModuleRepository::class);

        $names = array_map(static fn ($m) => $m->name, $repo->all());
        $this->assertSame(['Alpha', 'Middle', 'Zebra'], $names);

        $enabledNames = array_map(static fn ($m) => $m->name, $repo->enabled());
        $this->assertSame(['Alpha', 'Zebra'], $enabledNames);
        $this->assertNotContains('Middle', $enabledNames);
    }

    public function test_repository_cache_roundtrip_and_case_insensitive_lookup(): void
    {
        $this->makeModule('Orders');
        $this->makeModule('Users');

        /** @var ModuleRepository $repo */
        $repo = $this->app->make(ModuleRepository::class);

        $this->assertNotNull($repo->findByName('orders'));
        $this->assertNotNull($repo->findByName('USERS'));
        $this->assertNull($repo->findByName('Missing'));

        $repo->cache();
        $this->assertTrue($repo->hasCache());
        $this->assertFileExists($this->cacheFile);

        $cached = $repo->all(true);
        $this->assertCount(2, $cached);
        $this->assertContains('Orders', array_map(static fn ($m) => $m->name, $cached));

        $this->assertTrue($repo->clearCache());
        $this->assertFalse($repo->hasCache());
        $this->assertFalse($repo->clearCache());
    }

    public function test_skips_directories_without_module_json(): void
    {
        $this->makeModule('Valid');
        $this->files->ensureDirectoryExists($this->modulePath('NoManifest'));
        $this->files->put($this->modulePath('NoManifest/readme.txt'), 'ignore me');

        /** @var ModuleRepository $repo */
        $repo = $this->app->make(ModuleRepository::class);

        $names = array_map(static fn ($m) => $m->name, $repo->all());
        $this->assertSame(['Valid'], $names);
    }

    public function test_package_bindings_and_core_commands_are_registered(): void
    {
        $this->assertTrue($this->app->bound(ModuleRepository::class));
        $this->assertNotNull(config('modular.modules_path'));
        $this->assertNotNull(config('modular.cache_file'));

        foreach ([
            'modular:install',
            'modular:make',
            'modular:list',
            'modular:enable',
            'modular:disable',
            'modular:cache',
            'modular:clear',
            'modular:doctor',
            'modular:crud',
            'modular:rename',
            'modular:delete',
        ] as $command) {
            $this->assertTrue(
                $this->app->make('Illuminate\Contracts\Console\Kernel')->all() !== []
                && array_key_exists($command, $this->app->make('Illuminate\Contracts\Console\Kernel')->all()),
                "Expected artisan command [{$command}] to be registered."
            );
        }
    }

    public function test_install_then_make_then_status_happy_path(): void
    {
        $this->files->deleteDirectory($this->modulesPath);

        $this->artisan('modular:install')->assertSuccessful();
        $this->assertDirectoryExists($this->modulesPath);

        $this->artisan('modular:make', ['name' => 'Inventory', '--api' => true])->assertSuccessful();
        $this->assertFileExists($this->modulePath('Inventory/Routes/api.php'));
        $this->assertFileDoesNotExist($this->modulePath('Inventory/Routes/web.php'));

        $this->artisan('modular:status')->expectsOutputToContain('Package Installed')->assertSuccessful();
        $this->artisan('modular:list')->expectsOutputToContain('Inventory')->assertSuccessful();
    }
}
