<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\ResolvesModuleArguments;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeViewCommand extends Command
{
    use ResolvesModuleArguments;

    protected $signature = 'modular:view {target : Module name OR view name} {name? : View name when module is first} {--module= : Module name} {--m= : Module name alias}';

    protected $description = 'Generate a module Blade view with default template.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndNameWithHint(
                'modular:view User index  OR  index --module=User'
            );
            $created = $scaffolder->createView(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $name
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('View created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
