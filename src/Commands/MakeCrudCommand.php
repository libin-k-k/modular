<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeCrudCommand extends Command
{
    protected $signature = 'modular:crud {target : Module name OR resource name} {name? : Resource name when module is first argument} {--module= : Module name when using resource-first style} {--m= : Module name alias}';

    protected $description = 'Generate a full CRUD scaffold inside a module.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        $target = trim((string) $this->argument('target'));
        $nameArg = $this->argument('name');
        $name = is_string($nameArg) ? trim($nameArg) : '';
        $moduleOption = trim((string) ($this->option('module') ?: $this->option('m') ?: ''));

        if ($moduleOption !== '') {
            $module = $moduleOption;
            $resource = $target;
        } else {
            $module = $target;
            $resource = $name;
        }

        if ($module === '' || $resource === '') {
            $this->error('Both module and resource name are required. Use: modular:crud User Product  OR  Product --module=User');

            return self::FAILURE;
        }

        try {
            $created = $scaffolder->createCrud(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $resource
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('CRUD for [%s] created successfully in module [%s].', $resource, $module));
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
