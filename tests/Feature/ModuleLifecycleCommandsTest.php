<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Tests\TestCase;

class ModuleLifecycleCommandsTest extends TestCase
{
    private string $modulesPath;
    private string $cacheFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = (string) config('modular.modules_path');
        $this->cacheFile = (string) config('modular.cache_file');
        $files = new Filesystem();
        $files->deleteDirectory(dirname($this->modulesPath) . DIRECTORY_SEPARATOR . 'bootstrap');
        $files->deleteDirectory($this->modulesPath);
        $files->ensureDirectoryExists($this->modulesPath);

        $modulePath = $this->modulesPath . '/Catalog';
        $files->ensureDirectoryExists($modulePath);
        $files->put(
            $modulePath . '/module.json',
            json_encode([
                'name' => 'Catalog',
                'description' => 'Catalog Module',
                'version' => '1.0.0',
                'enabled' => true,
                'dependencies' => [],
            ], JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    public function test_it_lists_modules(): void
    {
        $this->artisan('modular:list')
            ->expectsOutputToContain('Catalog')
            ->assertSuccessful();
    }

    public function test_it_can_disable_and_enable_module(): void
    {
        $this->artisan('modular:disable', ['name' => 'Catalog'])
            ->assertSuccessful();

        $decoded = json_decode(file_get_contents($this->modulesPath . '/Catalog/module.json') ?: '', true);
        $this->assertFalse((bool) $decoded['enabled']);

        $this->artisan('modular:enable', ['name' => 'Catalog'])
            ->assertSuccessful();

        $decoded = json_decode(file_get_contents($this->modulesPath . '/Catalog/module.json') ?: '', true);
        $this->assertTrue((bool) $decoded['enabled']);
    }

    public function test_it_caches_modules(): void
    {
        $this->artisan('modular:cache')->assertSuccessful();
        $this->assertFileExists($this->cacheFile);

        /** @var array<string, array<string, mixed>> $cached */
        $cached = require $this->cacheFile;
        $this->assertCount(1, $cached);
        $first = reset($cached);
        $this->assertIsArray($first);
        $this->assertSame('Catalog', $first['name'] ?? null);
    }

    public function test_it_renames_module_and_maintains_logs_and_version_history(): void
    {
        $this->artisan('modular:rename', ['from' => 'Catalog', 'to' => 'ProductCatalog'])
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->modulesPath . '/Catalog');
        $this->assertDirectoryExists($this->modulesPath . '/ProductCatalog');

        $decoded = json_decode(file_get_contents($this->modulesPath . '/ProductCatalog/module.json') ?: '', true);
        $this->assertIsArray($decoded);
        $this->assertSame('ProductCatalog', $decoded['name'] ?? null);
        $this->assertSame('1.0.1', $decoded['version'] ?? null);

        $this->assertIsArray($decoded['rename_log'] ?? null);
        $this->assertCount(1, $decoded['rename_log']);
        $this->assertSame('Catalog', $decoded['rename_log'][0]['from'] ?? null);
        $this->assertSame('ProductCatalog', $decoded['rename_log'][0]['to'] ?? null);
        $this->assertSame('1.0.0', $decoded['rename_log'][0]['version_from'] ?? null);
        $this->assertSame('1.0.1', $decoded['rename_log'][0]['version_to'] ?? null);

        $this->assertIsArray($decoded['version_history'] ?? null);
        $this->assertCount(1, $decoded['version_history']);
        $this->assertSame('1.0.0', $decoded['version_history'][0]['from'] ?? null);
        $this->assertSame('1.0.1', $decoded['version_history'][0]['to'] ?? null);
    }

    public function test_it_clears_cache_file(): void
    {
        $this->artisan('modular:cache')->assertSuccessful();
        $this->assertFileExists($this->cacheFile);

        $this->artisan('modular:clear')
            ->expectsOutputToContain('cleared successfully')
            ->assertSuccessful();

        $this->assertFileDoesNotExist($this->cacheFile);
    }

    public function test_it_fails_rename_when_source_module_missing(): void
    {
        $this->artisan('modular:rename', ['from' => 'Unknown', 'to' => 'Any'])
            ->assertFailed();
    }

    public function test_it_fails_rename_when_target_module_exists(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists($this->modulesPath . '/Users');
        $files->put(
            $this->modulesPath . '/Users/module.json',
            json_encode([
                'name' => 'Users',
                'description' => 'Users Module',
                'version' => '1.0.0',
                'enabled' => true,
                'dependencies' => [],
            ], JSON_PRETTY_PRINT) . PHP_EOL
        );

        $this->artisan('modular:rename', ['from' => 'Catalog', 'to' => 'Users'])
            ->assertFailed();
    }

    public function test_it_can_force_rename_over_existing_target_module(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists($this->modulesPath . '/Users');
        $files->put(
            $this->modulesPath . '/Users/module.json',
            json_encode([
                'name' => 'Users',
                'description' => 'Users Module',
                'version' => '2.0.0',
                'enabled' => true,
                'dependencies' => [],
            ], JSON_PRETTY_PRINT) . PHP_EOL
        );

        $this->artisan('modular:rename', ['from' => 'Catalog', 'to' => 'Users', '--force' => true])
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->modulesPath . '/Catalog');
        $this->assertDirectoryExists($this->modulesPath . '/Users');
        $decoded = json_decode(file_get_contents($this->modulesPath . '/Users/module.json') ?: '', true);
        $this->assertSame('Users', $decoded['name'] ?? null);
        $this->assertSame('1.0.1', $decoded['version'] ?? null);
        $this->assertCount(1, $decoded['rename_log'] ?? []);
    }

    public function test_it_deletes_module_with_force_option(): void
    {
        $this->assertDirectoryExists($this->modulesPath . '/Catalog');

        $this->artisan('modular:delete', ['name' => 'Catalog', '--force' => true])
            ->expectsOutputToContain('deleted successfully')
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist($this->modulesPath . '/Catalog');
    }
}
