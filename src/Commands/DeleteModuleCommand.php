<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

class DeleteModuleCommand extends Command
{
    protected $signature = 'modular:delete {name : Module name} {--force : Delete without confirmation}';

    protected $description = 'Delete a module directory safely.';

    public function handle(ModuleRepository $repository): int
    {
        $name = (string) $this->argument('name');
        $force = (bool) $this->option('force');

        if (!$force && !$this->confirm(sprintf('Are you sure you want to delete module [%s]?', $name), false)) {
            $this->warn('Module deletion cancelled.');

            return self::SUCCESS;
        }

        try {
            $module = $repository->delete($name);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Module [%s] deleted successfully.', $module->name));

        return self::SUCCESS;
    }
}
