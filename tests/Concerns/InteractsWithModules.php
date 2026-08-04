<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Concerns;

use Illuminate\Filesystem\Filesystem;

trait InteractsWithModules
{
    protected string $modulesPath;
    protected string $cacheFile;
    protected Filesystem $files;

    protected function bootModuleWorkspace(): void
    {
        $this->modulesPath = (string) config('modular.modules_path');
        $this->cacheFile = (string) config('modular.cache_file');
        $this->files = new Filesystem();
        $this->files->deleteDirectory(dirname($this->modulesPath) . DIRECTORY_SEPARATOR . 'bootstrap');
        $this->files->deleteDirectory($this->modulesPath);
        $this->files->ensureDirectoryExists($this->modulesPath);
    }

    protected function makeModule(string $name): void
    {
        $this->artisan('modular:make', ['name' => $name])->assertSuccessful();
    }

    protected function modulePath(string $relative): string
    {
        return $this->modulesPath . '/' . ltrim(str_replace('\\', '/', $relative), '/');
    }

    protected function assertModuleFileContains(string $relative, string ...$needles): void
    {
        $path = $this->modulePath($relative);
        $this->assertFileExists($path);
        $content = $this->files->get($path);

        foreach ($needles as $needle) {
            $this->assertStringContainsString($needle, $content, "Missing [{$needle}] in {$relative}");
        }
    }
}
