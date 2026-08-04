<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\RefreshesModuleCache;
use Libinkk\Modular\Commands\Concerns\SuggestsModules;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

class RenameModuleCommand extends Command
{
    use RefreshesModuleCache;
    use SuggestsModules;

    protected $signature = 'modular:rename {from : Current module name} {to : New module name} {--force : Overwrite target module if it exists}';

    protected $description = 'Rename a module, update namespaces/files, and maintain rename stats in module.json.';

    public function handle(ModuleRepository $repository): int
    {
        $from = (string) $this->argument('from');
        $to = (string) $this->argument('to');

        try {
            $this->requireModule($repository, $from);
            $result = $repository->rename($from, $to, (bool) $this->option('force'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $module = $result['module'];
        $stats = $result['stats'];

        $this->refreshModuleCache($repository);
        $this->info(sprintf(
            'Module [%s] renamed to [%s]. New version: %s',
            $from,
            $module->name,
            $module->version
        ));
        $this->line(sprintf('Files renamed: %d', $stats['files_renamed']));
        $this->line(sprintf('Files updated: %d', $stats['files_updated']));
        $this->line(sprintf('Total changes: %d', $stats['total_changes']));

        return self::SUCCESS;
    }
}
