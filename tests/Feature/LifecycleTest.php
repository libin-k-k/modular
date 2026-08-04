<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Tests\Concerns\InteractsWithModules;
use Libinkk\Modular\Tests\TestCase;

class LifecycleTest extends TestCase
{
    use InteractsWithModules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootModuleWorkspace();
        $this->makeModule('Catalog');
    }

    public function test_list_enable_disable_cache_clear_and_doctor(): void
    {
        $this->artisan('modular:list')->expectsOutputToContain('Catalog')->assertSuccessful();

        $this->artisan('modular:disable', ['name' => 'Catalog'])->assertSuccessful();
        $this->assertFalse((bool) json_decode($this->files->get($this->modulePath('Catalog/module.json')), true)['enabled']);

        $this->artisan('modular:enable', ['name' => 'Catalog'])->assertSuccessful();
        $this->assertTrue((bool) json_decode($this->files->get($this->modulePath('Catalog/module.json')), true)['enabled']);

        $this->artisan('modular:cache')->assertSuccessful();
        $this->assertFileExists($this->cacheFile);
        $this->artisan('modular:list', ['--cached' => true])->expectsOutputToContain('Catalog')->assertSuccessful();

        $this->artisan('modular:clear')->expectsOutputToContain('cleared successfully')->assertSuccessful();
        $this->assertFileDoesNotExist($this->cacheFile);
        $this->artisan('modular:clear')->expectsOutputToContain('does not exist')->assertSuccessful();

        $this->artisan('modular:doctor')->expectsOutputToContain('Doctor check passed.')->assertSuccessful();
    }

    public function test_rename_updates_files_namespaces_and_json_stats(): void
    {
        $this->artisan('modular:controller', ['target' => 'Catalog', 'name' => 'ItemController'])->assertSuccessful();

        $this->artisan('modular:rename', ['from' => 'Catalog', 'to' => 'Store', '--force' => true])
            ->expectsOutputToContain('Files renamed:')
            ->expectsOutputToContain('Files updated:')
            ->expectsOutputToContain('Total changes:')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->modulePath('Catalog'));
        $this->assertDirectoryExists($this->modulePath('Store'));
        $this->assertFileExists($this->modulePath('Store/Providers/StoreServiceProvider.php'));
        $this->assertFileDoesNotExist($this->modulePath('Store/Providers/CatalogServiceProvider.php'));

        $this->assertModuleFileContains(
            'Store/Providers/StoreServiceProvider.php',
            'namespace Modules\\Store\\Providers;',
            'class StoreServiceProvider',
            "loadViewsFrom(__DIR__ . '/../Views', 'store')"
        );
        $this->assertModuleFileContains('Store/Controllers/ItemController.php', 'namespace Modules\\Store\\Controllers;');

        $manifest = json_decode($this->files->get($this->modulePath('Store/module.json')), true);
        $this->assertSame('Store', $manifest['name'] ?? null);
        $this->assertSame('1.0.1', $manifest['version'] ?? null);
        $this->assertArrayHasKey('files_renamed', $manifest['rename_log'][0] ?? []);
        $this->assertArrayHasKey('files_updated', $manifest['rename_log'][0] ?? []);
        $this->assertArrayHasKey('total_changes', $manifest['rename_log'][0] ?? []);
        $this->assertSame(
            $manifest['rename_log'][0]['total_changes'] ?? null,
            $manifest['last_rename_stats']['total_changes'] ?? null
        );
    }

    public function test_rename_conflict_and_force_overwrite(): void
    {
        $this->makeModule('Users');

        $this->artisan('modular:rename', ['from' => 'Catalog', 'to' => 'Users'])->assertFailed();

        $this->artisan('modular:rename', ['from' => 'Catalog', 'to' => 'Users', '--force' => true])
            ->expectsOutputToContain('Total changes:')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->modulePath('Catalog'));
        $this->assertFileExists($this->modulePath('Users/Providers/UsersServiceProvider.php'));
    }

    public function test_rename_fails_when_source_missing(): void
    {
        $this->artisan('modular:rename', ['from' => 'Unknown', 'to' => 'Any'])->assertFailed();
    }

    public function test_delete_removes_module(): void
    {
        $this->artisan('modular:delete', ['name' => 'Catalog', '--force' => true])
            ->expectsOutputToContain('deleted successfully')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->modulePath('Catalog'));
    }

    public function test_doctor_fails_for_broken_module(): void
    {
        $this->files->ensureDirectoryExists($this->modulePath('Broken'));
        $this->files->put(
            $this->modulePath('Broken/module.json'),
            json_encode([
                'name' => 'Broken',
                'description' => 'Broken',
                'version' => '1.0.0',
                'enabled' => true,
                'dependencies' => ['MissingDep'],
            ], JSON_PRETTY_PRINT) . PHP_EOL
        );

        $this->artisan('modular:doctor')->expectsOutputToContain('issue')->assertFailed();
        $this->files->deleteDirectory($this->modulePath('Broken'));
    }
}
