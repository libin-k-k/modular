<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeModuleCommand extends Command
{
    protected $signature = 'modular:make {name : Module name, e.g. User}';

    protected $description = 'Create a new module with default structure and module.json.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        $name = trim((string) $this->argument('name'));
        if ($name === '') {
            $this->error('Module name is required.');

            return self::FAILURE;
        }

        try {
            $modulePath = $scaffolder->create((string) config('modular.modules_path', base_path('Modules')), $name);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$name}] created successfully.");
        $this->line("Path: {$modulePath}");

        return self::SUCCESS;
    }
}
