<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Smart-merge a constructor dependency into an existing PHP class file.
 * Adds a use import and a promoted constructor property without wiping methods or constructor bodies.
 */
class ConstructorInjector
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    /**
     * @return bool true when the file was modified
     */
    public function inject(string $filePath, string $fqcn, ?string $paramName = null): bool
    {
        if (!$this->files->exists($filePath)) {
            return false;
        }

        $fqcn = ltrim($fqcn, '\\');
        $short = class_basename($fqcn);
        $paramName = ltrim($paramName ?? self::defaultParamName($short), '$');

        $content = $this->files->get($filePath);

        if ($this->hasConstructorParamType($content, $short, $fqcn)) {
            return false;
        }

        $original = $content;
        $content = $this->ensureUseStatement($content, $fqcn, $short);
        $content = $this->ensureConstructorParam($content, $short, $paramName);

        if ($content === $original) {
            return false;
        }

        $this->files->put($filePath, $content);

        return true;
    }

    public static function defaultParamName(string $shortName): string
    {
        if (str_ends_with($shortName, 'RepositoryInterface')) {
            return 'repository';
        }

        if (str_ends_with($shortName, 'Interface') && str_contains($shortName, 'Repository')) {
            return 'repository';
        }

        if (str_ends_with($shortName, 'Service')) {
            return 'service';
        }

        if (str_ends_with($shortName, 'Repository')) {
            return 'repository';
        }

        return lcfirst($shortName);
    }

    private function hasConstructorParamType(string $content, string $short, string $fqcn): bool
    {
        if (preg_match('/function\s+__construct\s*\((.*?)\)\s*(?::\s*\w+)?\s*\{/s', $content, $matches) !== 1) {
            return false;
        }

        $params = $matches[1];

        return (bool) preg_match(
            '/(?:\\\\?' . preg_quote($fqcn, '/') . '|' . preg_quote($short, '/') . ')\s+\$/i',
            $params
        );
    }

    private function ensureUseStatement(string $content, string $fqcn, string $short): string
    {
        if (preg_match('/^use\s+' . preg_quote($fqcn, '/') . '\s*;/m', $content) === 1) {
            return $content;
        }

        if (preg_match('/^use\s+[\w\\\\]+\\\\' . preg_quote($short, '/') . '\s*;/m', $content) === 1) {
            return $content;
        }

        $useLine = "use {$fqcn};";

        // Insert after the last existing use statement.
        if (preg_match_all('/^use\s+[^;]+;/m', $content, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $last = $matches[0][array_key_last($matches[0])];
            $insertAt = $last[1] + strlen($last[0]);

            return substr($content, 0, $insertAt) . "\n" . $useLine . substr($content, $insertAt);
        }

        if (preg_match('/^namespace\s+[^;]+;/m', $content, $nsMatch, PREG_OFFSET_CAPTURE) === 1) {
            $insertAt = $nsMatch[0][1] + strlen($nsMatch[0][0]);

            return substr($content, 0, $insertAt) . "\n\n" . $useLine . substr($content, $insertAt);
        }

        return $content;
    }

    private function ensureConstructorParam(string $content, string $short, string $paramName): string
    {
        $param = "private readonly {$short} \${$paramName}";

        if (preg_match('/function\s+__construct\s*\((.*?)\)(\s*(?::\s*\w+)?\s*\{)/s', $content, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $params = trim($matches[1][0]);
            $newParams = $params === ''
                ? "\n        {$param}\n    "
                : rtrim($params) . ",\n        {$param}\n    ";

            $start = $matches[0][1];
            $end = $matches[0][1] + strlen($matches[0][0]);
            $replacement = 'function __construct(' . $newParams . ')' . $matches[2][0];

            return substr($content, 0, $start) . $replacement . substr($content, $end);
        }

        if (preg_match('/(class\s+\w+[^{]*\{)/', $content, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return $content;
        }

        $insertAt = $matches[0][1] + strlen($matches[0][0]);
        $constructor = <<<PHP

    public function __construct(
        {$param}
    ) {
    }

PHP;

        return substr($content, 0, $insertAt) . $constructor . substr($content, $insertAt);
    }
}
