<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Support\ModuleRepository;

class DoctorModulesCommand extends Command
{
    protected $signature = 'modular:doctor';

    protected $description = 'Diagnose module health, PSR-4, providers, routes, cache, and dependencies.';

    public function handle(ModuleRepository $repository, Filesystem $files): int
    {
        $modules = $repository->all();
        $modulesPath = (string) config('modular.modules_path', base_path('Modules'));
        $issues = 0;
        $rows = [];

        if (!$files->isDirectory($modulesPath)) {
            $this->error(sprintf('Modules path missing: %s', $modulesPath));
            $issues++;
        }

        $composerPath = base_path('composer.json');
        $hasPsr4 = false;
        if ($files->exists($composerPath)) {
            $decoded = json_decode($files->get($composerPath), true);
            $hasPsr4 = is_array($decoded) && isset($decoded['autoload']['psr-4']['Modules\\']);
        }
        if (!$hasPsr4) {
            $this->warn('Composer PSR-4 mapping for Modules\\ is missing. Add "Modules\\\\": "Modules/" to composer.json, then run composer dump-autoload.');
        }

        if ($modules === []) {
            $this->warn('No modules found.');
            if ($issues > 0) {
                $this->error(sprintf('Doctor found %d issue(s).', $issues));

                return self::FAILURE;
            }
            $this->info('Doctor check passed.');

            return self::SUCCESS;
        }

        $enabledNames = [];
        $nameCounts = [];
        foreach ($modules as $module) {
            $key = strtolower($module->name);
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
            if ($module->enabled) {
                $enabledNames[] = $module->name;
            }
        }

        foreach ($modules as $module) {
            $notes = [];
            $health = 'ok';
            $providerPath = $module->path . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $module->name . 'ServiceProvider.php';
            $manifestPath = $module->path . DIRECTORY_SEPARATOR . 'module.json';
            $routesDir = $module->path . DIRECTORY_SEPARATOR . 'Routes';
            $migrationsDir = $module->path . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
            $configDir = $module->path . DIRECTORY_SEPARATOR . 'Config';

            if (!$files->exists($manifestPath)) {
                $health = 'issue';
                $notes[] = 'missing module.json';
                $issues++;
            } else {
                $raw = $files->get($manifestPath);
                if (json_decode($raw, true) === null && json_last_error() !== JSON_ERROR_NONE) {
                    $health = 'issue';
                    $notes[] = 'invalid JSON';
                    $issues++;
                }
            }

            if (!$files->exists($providerPath)) {
                $health = 'issue';
                $notes[] = 'missing provider';
                $issues++;
            }

            if (($nameCounts[strtolower($module->name)] ?? 0) > 1) {
                $health = 'issue';
                $notes[] = 'duplicate module name';
                $issues++;
            }

            foreach ($module->dependencies as $dependency) {
                if (!in_array($dependency, $enabledNames, true)) {
                    $health = 'issue';
                    $notes[] = 'missing enabled dep: ' . $dependency;
                    $issues++;
                }
            }

            if ($module->enabled) {
                if (!$files->isDirectory($routesDir) || ($files->files($routesDir) === [])) {
                    $notes[] = 'no route files';
                }
                if (!$files->isDirectory($migrationsDir)) {
                    $notes[] = 'missing migrations folder';
                }
                if (!$files->isDirectory($configDir)) {
                    $notes[] = 'missing config folder';
                }
            } else {
                $notes[] = 'not loaded by design';
            }

            $expectedNamespace = 'Modules\\' . $module->name;
            if ($files->exists($providerPath)) {
                $provider = $files->get($providerPath);
                if (!str_contains($provider, 'namespace ' . $expectedNamespace . '\\Providers')) {
                    $health = 'issue';
                    $notes[] = 'provider namespace mismatch';
                    $issues++;
                }
            }

            $rows[] = [
                $module->name,
                $module->enabled ? 'enabled' : 'disabled',
                $health,
                $files->exists($providerPath) ? 'yes' : 'no',
                $notes === [] ? '-' : implode('; ', $notes),
            ];
        }

        if ($repository->hasCache() && (bool) config('modular.prefer_cache', true)) {
            $this->line('Cache: present (prefer_cache=true). Run modular:cache after lifecycle changes if auto_refresh_cache is off.');
        }

        $this->table(['Module', 'Status', 'Health', 'Provider', 'Notes'], $rows);

        if ($issues > 0) {
            $this->error(sprintf('Doctor found %d issue(s).', $issues));

            return self::FAILURE;
        }

        $this->info('Doctor check passed.');

        return self::SUCCESS;
    }
}
