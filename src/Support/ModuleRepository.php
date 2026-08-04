<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class ModuleRepository
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $modulesPath,
        private readonly string $cacheFile,
        private readonly ModuleRenamer $renamer,
    ) {
    }

    /**
     * @return list<Module>
     */
    public function all(bool $useCache = false): array
    {
        if ($useCache && $this->hasCache()) {
            /** @var array<string, array<string, mixed>> $cached */
            $cached = (array) $this->files->getRequire($this->cacheFile);
            $modules = [];
            foreach ($cached as $path => $data) {
                if (is_array($data)) {
                    $modules[] = Module::fromArray($data, $path);
                }
            }

            return $modules;
        }

        if (!$this->files->isDirectory($this->modulesPath)) {
            return [];
        }

        $modules = [];
        foreach ($this->files->directories($this->modulesPath) as $modulePath) {
            $manifestPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
            if (!$this->files->exists($manifestPath)) {
                continue;
            }

            $decoded = json_decode($this->files->get($manifestPath), true);
            if (!is_array($decoded)) {
                continue;
            }

            $modules[] = Module::fromArray($decoded, $modulePath);
        }

        usort($modules, static fn (Module $a, Module $b): int => strcmp($a->name, $b->name));

        return $modules;
    }

    /**
     * @return list<Module>
     */
    public function enabled(bool $useCache = false): array
    {
        return array_values(array_filter(
            $this->all($useCache),
            static fn (Module $module): bool => $module->enabled
        ));
    }

    public function hasCache(): bool
    {
        return $this->files->exists($this->cacheFile);
    }

    public function findByName(string $name): ?Module
    {
        foreach ($this->all() as $module) {
            if (strcasecmp($module->name, $name) === 0) {
                return $module;
            }
        }

        return null;
    }

    public function setEnabled(string $name, bool $enabled): Module
    {
        $module = $this->findByName($name);
        if ($module === null) {
            throw new RuntimeException(sprintf('Module [%s] was not found.', $name));
        }

        $payload = $this->readManifest($module->path);
        $payload['enabled'] = $enabled;

        $this->writeManifest($module->path, $payload, $module->name);

        return Module::fromArray($payload, $module->path);
    }

    /**
     * @return array{
     *     module: Module,
     *     stats: array{files_renamed: int, files_updated: int, total_changes: int}
     * }
     */
    public function rename(string $from, string $to, bool $force = false): array
    {
        $fromModule = $this->findByName($from);
        if ($fromModule === null) {
            throw new RuntimeException(sprintf('Module [%s] was not found.', $from));
        }

        $to = trim($to);
        if ($to === '') {
            throw new RuntimeException('New module name is required.');
        }

        $targetModule = $this->findByName($to);
        if ($targetModule !== null && !$force) {
            throw new RuntimeException(sprintf('Module [%s] already exists.', $to));
        }

        return $this->renamer->rename(
            $fromModule,
            $to,
            $this->modulesPath,
            $this->readManifest($fromModule->path),
            $force
        );
    }

    public function delete(string $name): Module
    {
        $module = $this->findByName($name);
        if ($module === null) {
            throw new RuntimeException(sprintf('Module [%s] was not found.', $name));
        }

        if (!$this->files->deleteDirectory($module->path)) {
            throw new RuntimeException(sprintf('Could not delete module directory [%s].', $module->path));
        }

        return $module;
    }

    public function cache(): void
    {
        $payload = [];
        foreach ($this->all(false) as $module) {
            $payload[$module->path] = $module->toArray();
        }

        $this->files->ensureDirectoryExists(dirname($this->cacheFile));
        $export = var_export($payload, true);
        $this->files->put($this->cacheFile, "<?php\n\nreturn {$export};\n");
    }

    public function clearCache(): bool
    {
        if (!$this->hasCache()) {
            return false;
        }

        return $this->files->delete($this->cacheFile);
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $modulePath): array
    {
        $manifestPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        if (!$this->files->exists($manifestPath)) {
            throw new RuntimeException(sprintf('module.json not found for [%s].', basename($modulePath)));
        }

        $decoded = json_decode($this->files->get($manifestPath), true);
        if (!is_array($decoded)) {
            throw new RuntimeException(sprintf('Invalid module.json for [%s].', basename($modulePath)));
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeManifest(string $modulePath, array $payload, string $moduleName): void
    {
        $manifestPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException(sprintf('Could not encode module manifest for [%s].', $moduleName));
        }

        $this->files->put($manifestPath, $json . PHP_EOL);
    }
}
