<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class ModuleRenamer
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array{
     *     module: Module,
     *     stats: array{files_renamed: int, files_updated: int, total_changes: int}
     * }
     */
    public function rename(
        Module $fromModule,
        string $to,
        string $modulesPath,
        array $manifest,
        bool $force = false
    ): array {
        $to = trim($to);
        if ($to === '') {
            throw new RuntimeException('New module name is required.');
        }

        $newPath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $to;
        if ($this->files->exists($newPath) && !$force) {
            throw new RuntimeException(sprintf('Target module path [%s] already exists.', $newPath));
        }
        if ($this->files->exists($newPath) && $force) {
            $this->files->deleteDirectory($newPath);
        }

        $previousVersion = (string) ($manifest['version'] ?? '1.0.0');
        $nextVersion = $this->bumpPatchVersion($previousVersion);
        $timestamp = date(DATE_ATOM);
        $oldName = $fromModule->name;

        if (!$this->moveDirectory($fromModule->path, $newPath)) {
            throw new RuntimeException(sprintf('Could not rename module directory [%s] to [%s].', $fromModule->path, $newPath));
        }

        $stats = $this->rewriteModuleFiles($newPath, $oldName, $to);

        $manifest['name'] = $to;
        $manifest['version'] = $nextVersion;
        $manifest['rename_log'] = $this->appendListEntry($manifest['rename_log'] ?? [], [
            'from' => $oldName,
            'to' => $to,
            'at' => $timestamp,
            'version_from' => $previousVersion,
            'version_to' => $nextVersion,
            'files_renamed' => $stats['files_renamed'],
            'files_updated' => $stats['files_updated'],
            'total_changes' => $stats['total_changes'],
        ]);
        $manifest['version_history'] = $this->appendListEntry($manifest['version_history'] ?? [], [
            'from' => $previousVersion,
            'to' => $nextVersion,
            'at' => $timestamp,
            'reason' => sprintf('Module renamed from %s to %s', $oldName, $to),
        ]);
        $manifest['last_rename_stats'] = $stats;

        $this->writeManifest($newPath, $manifest, $to);

        return [
            'module' => Module::fromArray($manifest, $newPath),
            'stats' => $stats,
        ];
    }

    private function moveDirectory(string $from, string $to): bool
    {
        $attempts = 5;
        for ($i = 0; $i < $attempts; $i++) {
            try {
                if ($this->files->move($from, $to)) {
                    return true;
                }
            } catch (\Throwable) {
                // Windows can briefly lock directories after deleteDirectory during --force.
            }

            usleep(50_000 * ($i + 1));
        }

        return false;
    }

    /**
     * @return array{files_renamed: int, files_updated: int, total_changes: int}
     */
    private function rewriteModuleFiles(string $modulePath, string $from, string $to): array
    {
        $filesRenamed = 0;
        $filesUpdated = 0;

        $oldProvider = $modulePath . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $from . 'ServiceProvider.php';
        $newProvider = $modulePath . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $to . 'ServiceProvider.php';
        if ($this->files->exists($oldProvider) && $oldProvider !== $newProvider) {
            $this->files->move($oldProvider, $newProvider);
            $filesRenamed++;
        }

        foreach ($this->files->allFiles($modulePath) as $file) {
            if (strtolower($file->getFilename()) === 'module.json') {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['php', 'json', 'md', 'stub', 'txt'], true)) {
                continue;
            }

            $pathname = $file->getPathname();
            $content = $this->files->get($pathname);
            $updated = $this->replaceModuleReferences($content, $from, $to);

            if ($updated !== $content) {
                $this->files->put($pathname, $updated);
                $filesUpdated++;
            }
        }

        return [
            'files_renamed' => $filesRenamed,
            'files_updated' => $filesUpdated,
            'total_changes' => $filesRenamed + $filesUpdated,
        ];
    }

    private function replaceModuleReferences(string $content, string $from, string $to): string
    {
        $fromLower = strtolower($from);
        $toLower = strtolower($to);

        $replacements = [
            'Modules\\' . $from => 'Modules\\' . $to,
            'Modules/' . $from => 'Modules/' . $to,
            $from . 'ServiceProvider' => $to . 'ServiceProvider',
            "'" . $fromLower . '.' => "'" . $toLower . '.',
            '"' . $fromLower . '.' => '"' . $toLower . '.',
        ];

        $updated = str_replace(array_keys($replacements), array_values($replacements), $content);

        $updated = preg_replace(
            '/(loadViewsFrom\([^,]+,\s*)([\'"])' . preg_quote($fromLower, '/') . '\2/',
            '$1$2' . $toLower . '$2',
            $updated
        ) ?? $updated;

        $updated = preg_replace(
            '/(loadTranslationsFrom\([^,]+,\s*)([\'"])' . preg_quote($fromLower, '/') . '\2/',
            '$1$2' . $toLower . '$2',
            $updated
        ) ?? $updated;

        return $updated;
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
