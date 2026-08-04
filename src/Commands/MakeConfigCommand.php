<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\ResolvesModuleArguments;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeConfigCommand extends Command
{
    use ResolvesModuleArguments;

    protected $signature = 'modular:config {target : Module name OR config name} {name? : Config name when module is first} {--module= : Module name} {--m= : Module name alias}';

    protected $description = 'Generate a module config file with default template.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndNameWithHint(
                'modular:config User settings  OR  settings --module=User'
            );
            $created = $scaffolder->createConfig(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $name
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Config created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
