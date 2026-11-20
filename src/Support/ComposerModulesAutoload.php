<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Safely adds only the Modules\\ PSR-4 mapping to an application composer.json.
 */
final class ComposerModulesAutoload
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    /**
     * @return array{changed: bool, path: string, mapping: string}
     */
    public function ensure(string $composerPath, string $autoloadPath): array
    {
        if (!$this->files->exists($composerPath)) {
            throw new RuntimeException('composer.json not found.');
        }

        $autoloadPath = rtrim(str_replace('\\', '/', $autoloadPath), '/') . '/';
        $raw = $this->files->get($composerPath);
        $composer = json_decode($raw, true);
        if (!is_array($composer)) {
            throw new RuntimeException('composer.json is invalid JSON.');
        }

        $existing = $composer['autoload']['psr-4']['Modules\\'] ?? null;
        if (is_string($existing) && rtrim(str_replace('\\', '/', $existing), '/') . '/' === $autoloadPath) {
            return [
                'changed' => false,
                'path' => $composerPath,
                'mapping' => $autoloadPath,
            ];
        }

        if (isset($composer['autoload']) && !is_array($composer['autoload'])) {
            throw new RuntimeException('composer.json autoload must be an object/array.');
        }
        if (isset($composer['autoload']['psr-4']) && !is_array($composer['autoload']['psr-4'])) {
            throw new RuntimeException('composer.json autoload.psr-4 must be an object/array.');
        }

        $preservedAutoload = $composer['autoload'] ?? [];
        $preservedPsr4 = $preservedAutoload['psr-4'] ?? [];

        $composer['autoload'] = $preservedAutoload;
        $composer['autoload']['psr-4'] = $preservedPsr4;
        $composer['autoload']['psr-4']['Modules\\'] = $autoloadPath;

        foreach ($preservedAutoload as $key => $value) {
            if ($key === 'psr-4') {
                continue;
            }
            if (($composer['autoload'][$key] ?? null) !== $value) {
                throw new RuntimeException('Refusing to write composer.json: unrelated autoload keys would change.');
            }
        }

        foreach ($preservedPsr4 as $namespace => $path) {
            if ($namespace === 'Modules\\') {
                continue;
            }
            if (($composer['autoload']['psr-4'][$namespace] ?? null) !== $path) {
                throw new RuntimeException('Refusing to write composer.json: existing PSR-4 mappings would change.');
            }
        }

        $encoded = json_encode(
            $composer,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        if ($encoded === false) {
            throw new RuntimeException('Could not encode composer.json.');
        }

        $this->files->put($composerPath, $encoded . PHP_EOL);

        return [
            'changed' => true,
            'path' => $composerPath,
            'mapping' => $autoloadPath,
        ];
    }
}
