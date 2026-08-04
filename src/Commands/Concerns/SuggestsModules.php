<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands\Concerns;

use Libinkk\Modular\Support\Module;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

trait SuggestsModules
{
    /**
     * @return never
     */
    protected function failWithModuleSuggestion(ModuleRepository $repository, string $name): void
    {
        $suggestions = $this->suggestModuleNames($repository, $name);
        $message = sprintf('Module [%s] was not found.', $name);

        if ($suggestions !== []) {
            $message .= "\nDid you mean...\n  " . implode("\n  ", $suggestions);
        }

        throw new RuntimeException($message);
    }

    /**
     * @return list<string>
     */
    protected function suggestModuleNames(ModuleRepository $repository, string $name, int $limit = 5): array
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return [];
        }

        $scored = [];
        foreach ($repository->all() as $module) {
            $candidate = strtolower($module->name);
            $distance = levenshtein($name, $candidate);
            $contains = str_contains($candidate, $name) || str_contains($name, $candidate);
            if ($distance <= max(3, (int) floor(strlen($candidate) / 2)) || $contains) {
                $scored[$module->name] = $contains ? $distance - 10 : $distance;
            }
        }

        asort($scored);

        return array_slice(array_keys($scored), 0, $limit);
    }

    protected function requireModule(ModuleRepository $repository, string $name): Module
    {
        $module = $repository->findByName($name);
        if ($module === null) {
            $this->failWithModuleSuggestion($repository, $name);
        }

        return $module;
    }
}
