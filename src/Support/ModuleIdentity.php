<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use RuntimeException;

/**
 * Validates module / artifact names and keeps generated paths inside Modules/.
 */
final class ModuleIdentity
{
    public static function assertModuleName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Module name is required.');
        }

        self::rejectDangerousInput($name, 'Module name');

        if (preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new RuntimeException(sprintf(
                'Module name [%s] must be a valid PHP identifier (start with a letter; letters, numbers, underscore only).',
                $name
            ));
        }

        return $name;
    }

    /**
     * Nested artifact path like API/Admin/UserController.
     */
    public static function assertArtifactName(string $name): string
    {
        $name = trim(str_replace('\\', '/', $name), '/');
        if ($name === '') {
            throw new RuntimeException('Artifact name is required.');
        }

        self::rejectDangerousInput($name, 'Artifact name');

        $parts = array_values(array_filter(explode('/', $name), static fn (string $part): bool => $part !== ''));
        if ($parts === []) {
            throw new RuntimeException('Artifact name is invalid.');
        }

        foreach ($parts as $part) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part) !== 1) {
                throw new RuntimeException(sprintf(
                    'Artifact name segment [%s] must be a valid PHP identifier.',
                    $part
                ));
            }
        }

        return implode('/', $parts);
    }

    /**
     * Relative resource paths for views/config/lang/migrations (snake or nested).
     */
    public static function assertResourcePath(string $name, string $label = 'Resource name'): string
    {
        $name = trim(str_replace('\\', '/', $name), '/');
        if ($name === '') {
            throw new RuntimeException(sprintf('%s is required.', $label));
        }

        self::rejectDangerousInput($name, $label);

        $parts = array_values(array_filter(explode('/', $name), static fn (string $part): bool => $part !== ''));
        if ($parts === []) {
            throw new RuntimeException(sprintf('%s is invalid.', $label));
        }

        foreach ($parts as $part) {
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $part) !== 1) {
                throw new RuntimeException(sprintf(
                    '%s segment [%s] contains invalid characters.',
                    $label,
                    $part
                ));
            }
        }

        return implode('/', $parts);
    }

    public static function modulePath(string $modulesPath, string $moduleName): string
    {
        $moduleName = self::assertModuleName($moduleName);
        $path = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        self::assertInsideModulesPath($modulesPath, $path);

        return $path;
    }

    public static function assertInsideModulesPath(string $modulesPath, string $path): void
    {
        $root = self::normalizePath(rtrim(str_replace('\\', '/', $modulesPath), '/') . '/');
        $target = self::normalizePath(str_replace('\\', '/', $path));

        $rootTrim = rtrim($root, '/');
        if ($target === $rootTrim) {
            return;
        }

        if (!str_starts_with($target, $root)) {
            throw new RuntimeException('Resolved path escapes the configured modules directory.');
        }
    }

    private static function rejectDangerousInput(string $value, string $label): void
    {
        if (str_contains($value, "\0")) {
            throw new RuntimeException(sprintf('%s contains a null byte.', $label));
        }

        $normalized = str_replace('\\', '/', $value);
        if (str_contains($normalized, "\0")) {
            throw new RuntimeException(sprintf('%s contains a null byte.', $label));
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                throw new RuntimeException(sprintf('%s must not contain path traversal segments.', $label));
            }
        }

        if (preg_match('#^[a-zA-Z]:|^/#', $normalized) === 1) {
            throw new RuntimeException(sprintf('%s must not be an absolute path.', $label));
        }
    }

    private static function normalizePath(string $path): string
    {
        $absolute = str_starts_with($path, '/');
        $trailing = str_ends_with($path, '/') && $path !== '/';
        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($parts === []) {
                    throw new RuntimeException('Resolved path escapes the configured modules directory.');
                }
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        $normalized = ($absolute ? '/' : '') . implode('/', $parts);
        if ($trailing && $normalized !== '/') {
            $normalized .= '/';
        }

        return $normalized === '' && $absolute ? '/' : $normalized;
    }
}
