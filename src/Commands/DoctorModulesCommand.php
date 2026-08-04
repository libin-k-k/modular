<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Support\ModuleRepository;

class DoctorModulesCommand extends Command
{
    protected $signature = 'modular:doctor';

    protected $description = 'Diagnose module health, provider wiring, and dependencies.';

    public function handle(ModuleRepository $repository, Filesystem $files): int
    {
        $modules = $repository->all();
        if ($modules === []) {
            $this->warn('No modules found.');

            return self::SUCCESS;
        }

        $enabledNames = [];
        foreach ($modules as $module) {
            if ($module->enabled) {
                $enabledNames[] = $module->name;
            }
        }

        $rows = [];
        $issues = 0;
        foreach ($modules as $module) {
            $providerPath = $module->path . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $module->name . 'ServiceProvider.php';
            $providerExists = $files->exists($providerPath);
            $dependencyIssues = [];
            foreach ($module->dependencies as $dependency) {
                if (!in_array($dependency, $enabledNames, true)) {
                    $dependencyIssues[] = $dependency;
                }
            }

            $status = $module->enabled ? 'enabled' : 'disabled';
            $health = 'ok';
            $notes = [];

            if (!$providerExists) {
                $health = 'issue';
                $notes[] = 'missing provider';
                $issues++;
            }
            if ($dependencyIssues !== []) {
                $health = 'issue';
                $notes[] = 'missing enabled deps: ' . implode(',', $dependencyIssues);
                $issues++;
            }
            if (!$module->enabled) {
                $notes[] = 'not loaded by design';
            }

            $rows[] = [
                $module->name,
                $status,
                $health,
                $providerExists ? 'yes' : 'no',
                $notes === [] ? '-' : implode('; ', $notes),
            ];
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
