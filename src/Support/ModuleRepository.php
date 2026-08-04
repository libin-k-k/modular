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
        private readonly string $cacheFile
    ) {
    }

    /**
     * @return list<Module>
     */
    public function all(bool $useCache = false): array
    {
        if ($useCache && $this->files->exists($this->cacheFile)) {
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

    public function rename(string $from, string $to, bool $force = false): Module
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

        $newPath = rtrim($this->modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $to;
        if ($this->files->exists($newPath) && !$force) {
            throw new RuntimeException(sprintf('Target module path [%s] already exists.', $newPath));
        }
        if ($this->files->exists($newPath) && $force) {
            $this->files->deleteDirectory($newPath);
        }

        $payload = $this->readManifest($fromModule->path);
        $previousVersion = (string) ($payload['version'] ?? '1.0.0');
        $nextVersion = $this->bumpPatchVersion($previousVersion);
        $timestamp = date(DATE_ATOM);

        $payload['name'] = $to;
        $payload['version'] = $nextVersion;
        $payload['rename_log'] = $this->appendListEntry($payload['rename_log'] ?? [], [
            'from' => $fromModule->name,
            'to' => $to,
            'at' => $timestamp,
            'version_from' => $previousVersion,
            'version_to' => $nextVersion,
        ]);
        $payload['version_history'] = $this->appendListEntry($payload['version_history'] ?? [], [
            'from' => $previousVersion,
            'to' => $nextVersion,
            'at' => $timestamp,
            'reason' => sprintf('Module renamed from %s to %s', $fromModule->name, $to),
        ]);

        if (!$this->files->move($fromModule->path, $newPath)) {
            throw new RuntimeException(sprintf('Could not rename module directory [%s] to [%s].', $fromModule->path, $newPath));
        }

        $this->writeManifest($newPath, $payload, $to);

        return Module::fromArray($payload, $newPath);
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
        if (!$this->files->exists($this->cacheFile)) {
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

    /**
     * @param mixed $value
     * @param array<string, mixed> $entry
     * @return list<array<string, mixed>>
     */
    private function appendListEntry(mixed $value, array $entry): array
    {
        $list = [];
        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_array($item)) {
                    $list[] = $item;
                }
            }
        }

        $list[] = $entry;

        return $list;
    }

    private function bumpPatchVersion(string $version): string
    {
        $version = trim($version);
        if (preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches) !== 1) {
            return '1.0.1';
        }

        $major = (int) $matches[1];
        $minor = (int) $matches[2];
        $patch = (int) $matches[3] + 1;

        return sprintf('%d.%d.%d', $major, $minor, $patch);
    }
}
