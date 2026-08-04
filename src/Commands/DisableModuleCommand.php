<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleRepository;
use RuntimeException;

class DisableModuleCommand extends Command
{
    protected $signature = 'modular:disable {name : Module name}';

    protected $description = 'Disable a module in module.json.';

    public function handle(ModuleRepository $repository): int
    {
        $name = (string) $this->argument('name');

        try {
            $module = $repository->setEnabled($name, false);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$module->name}] disabled.");

        return self::SUCCESS;
    }
}
