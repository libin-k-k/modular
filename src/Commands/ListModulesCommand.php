<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleRepository;

class ListModulesCommand extends Command
{
    protected $signature = 'modular:list {--cached : Read from module cache file}';

    protected $description = 'List all discovered modules.';

    public function handle(ModuleRepository $repository): int
    {
        $modules = $repository->all((bool) $this->option('cached'));
        if ($modules === []) {
            $this->warn('No modules found.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($modules as $module) {
            $rows[] = [
                $module->name,
                $module->enabled ? 'enabled' : 'disabled',
                $module->version,
                implode(', ', $module->dependencies),
                $module->path,
            ];
        }

        $this->table(
            ['Name', 'Status', 'Version', 'Dependencies', 'Path'],
            $rows
        );

        return self::SUCCESS;
    }
}
