<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands\Concerns;

use RuntimeException;

trait ResolvesModuleArguments
{
    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveModuleAndNameWithHint(string $errorHint): array
    {
        $target = trim((string) $this->argument('target'));
        $nameArg = $this->argument('name');
        $name = is_string($nameArg) ? trim($nameArg) : '';
        $moduleOption = trim((string) ($this->option('module') ?: $this->option('m') ?: ''));

        if ($target === '') {
            throw new RuntimeException('Target is required.');
        }

        if ($moduleOption !== '') {
            return [$moduleOption, $target];
        }

        if ($name === '') {
            throw new RuntimeException('Both module and name are required. Use: ' . $errorHint);
        }

        return [$target, $name];
    }
}
