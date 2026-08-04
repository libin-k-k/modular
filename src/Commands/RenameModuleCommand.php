<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

class RenameModuleCommand extends Command
{
    protected $signature = 'modular:rename {from : Current module name} {to : New module name} {--force : Overwrite target module if it exists}';

    protected $description = 'Rename a module and maintain rename/version logs in module.json.';

    public function handle(ModuleRepository $repository): int
    {
        $from = (string) $this->argument('from');
        $to = (string) $this->argument('to');

        try {
            $module = $repository->rename($from, $to, (bool) $this->option('force'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Module [%s] renamed to [%s]. New version: %s',
            $from,
            $module->name,
            $module->version
        ));

        return self::SUCCESS;
    }
}
