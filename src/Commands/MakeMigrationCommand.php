<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\ResolvesModuleArguments;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeMigrationCommand extends Command
{
    use ResolvesModuleArguments;

    protected $signature = 'modular:migration {target : Module name OR migration name} {name? : Migration name when module is first argument} {--module= : Module name when using name-first style} {--m= : Module name alias}';

    protected $description = 'Generate a migration inside a module.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndNameWithHint(
                'modular:migration User create_users_table  OR  create_users_table --module=User'
            );
            $created = $scaffolder->createMigration(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $name
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Migration created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
