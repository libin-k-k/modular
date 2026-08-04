<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeMigrationCommand extends Command
{
    protected $signature = 'modular:migration {target : Module name OR migration name} {name? : Migration name when module is first argument} {--module= : Module name when using name-first style} {--m= : Module name alias}';

    protected $description = 'Generate a migration inside a module.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        return $this->runCreate($scaffolder, 'migration');
    }

    private function runCreate(ModuleScaffolder $scaffolder, string $label): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndName();
            $created = $scaffolder->createMigration(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $name
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(ucfirst($label) . ' created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveModuleAndName(): array
    {
        $target = trim((string) $this->argument('target'));
        $nameArg = $this->argument('name');
        $name = is_string($nameArg) ? trim($nameArg) : '';
        $moduleOption = trim((string) ($this->option('module') ?: $this->option('m') ?: ''));

        if ($moduleOption !== '') {
            return [$moduleOption, $target];
        }

        if ($name === '') {
            throw new RuntimeException('Both module and migration name are required. Use: modular:migration User create_users_table  OR  create_users_table --module=User');
        }

        return [$target, $name];
    }
}
