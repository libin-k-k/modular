<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

class EnableModuleCommand extends Command
{
    protected $signature = 'modular:enable {name : Module name}';

    protected $description = 'Enable a module in module.json.';

    public function handle(ModuleRepository $repository): int
    {
        $name = (string) $this->argument('name');

        try {
            $module = $repository->setEnabled($name, true);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$module->name}] enabled.");

        return self::SUCCESS;
    }
}
