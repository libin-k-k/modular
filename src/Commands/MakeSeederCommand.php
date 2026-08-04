<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\ResolvesModuleArguments;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeSeederCommand extends Command
{
    use ResolvesModuleArguments;

    protected $signature = 'modular:seeder {target : Module name OR seeder name} {name? : Seeder name when module is first argument} {--module= : Module name when using name-first style} {--m= : Module name alias}';

    protected $description = 'Generate a seeder inside a module.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndNameWithHint(
                'modular:seeder User UserSeeder  OR  UserSeeder --module=User'
            );
            $created = $scaffolder->createSeeder(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $name
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Seeder created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
