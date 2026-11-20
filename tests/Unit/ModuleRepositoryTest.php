<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Support\ModuleRenamer;
use Libinkk\Modular\Support\ModuleRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleRepositoryTest extends TestCase
{
    private Filesystem $files;
    private string $modulesPath;
    private string $cacheFile;
    private ModuleRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $base = sys_get_temp_dir() . '/modular_repo_test_' . uniqid('', true);
        $this->modulesPath = $base . '/Modules';
        $this->cacheFile = $base . '/cache/modular_modules.php';

        $this->files->ensureDirectoryExists($this->modulesPath);

        $this->repo = new ModuleRepository(
            $this->files,
            $this->modulesPath,
            $this->cacheFile,
            new ModuleRenamer($this->files)
        );
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory(dirname($this->modulesPath));
        parent::tearDown();
    }

    public function test_all_returns_empty_when_modules_path_missing(): void
    {
        $this->files->deleteDirectory($this->modulesPath);

        $this->assertSame([], $this->repo->all());
        $this->assertSame([], $this->repo->enabled());
    }

    public function test_set_enabled_updates_manifest(): void
    {
        $this->writeModule('Catalog', enabled: true);

        $updated = $this->repo->setEnabled('Catalog', false);

        $this->assertFalse($updated->enabled);
        $manifest = json_decode($this->files->get($this->modulesPath . '/Catalog/module.json'), true);
        $this->assertFalse($manifest['enabled']);
    }

    public function test_set_enabled_throws_when_module_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module [Ghost] was not found.');

        $this->repo->setEnabled('Ghost', true);
    }

    public function test_delete_removes_directory_and_throws_when_missing(): void
    {
        $this->writeModule('Temp');

        $deleted = $this->repo->delete('Temp');
        $this->assertSame('Temp', $deleted->name);
        $this->assertDirectoryDoesNotExist($this->modulesPath . '/Temp');

        $this->expectException(RuntimeException::class);
        $this->repo->delete('Temp');
    }

    public function test_rename_rejects_empty_target_name(): void
    {
        $this->writeModule('From');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module name is required.');

        $this->repo->rename('From', '   ');
    }

    public function test_rename_rejects_existing_target_without_force(): void
    {
        $this->writeModule('From');
        $this->writeModule('To');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module [To] already exists.');

        $this->repo->rename('From', 'To');
    }

    public function test_invalid_json_manifest_is_skipped(): void
    {
        $path = $this->modulesPath . '/Broken';
        $this->files->ensureDirectoryExists($path);
        $this->files->put($path . '/module.json', '{not-json');

        $this->writeModule('Ok');

        $names = array_map(static fn ($m) => $m->name, $this->repo->all());
        $this->assertSame(['Ok'], $names);
    }

    private function writeModule(string $name, bool $enabled = true): void
    {
        $path = $this->modulesPath . '/' . $name;
        $this->files->ensureDirectoryExists($path);
        $this->files->put(
            $path . '/module.json',
            json_encode([
                'name' => $name,
                'description' => "{$name} Module",
                'version' => '1.0.0',
                'enabled' => $enabled,
                'dependencies' => [],
            ], JSON_PRETTY_PRINT) . PHP_EOL
        );
    }
}
