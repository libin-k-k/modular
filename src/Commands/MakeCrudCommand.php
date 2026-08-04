<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\RefreshesModuleCache;
use Libinkk\Modular\Commands\Concerns\ResolvesModuleArguments;
use Libinkk\Modular\Commands\Concerns\SuggestsModules;
use Libinkk\Modular\Support\ModuleRepository;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeCrudCommand extends Command
{
    use RefreshesModuleCache;
    use ResolvesModuleArguments;
    use SuggestsModules;

    protected $signature = 'modular:crud
        {target : Module name OR resource name}
        {name? : Resource name when module is first argument}
        {--module= : Module name when using resource-first style} {--m= : Module name alias}
        {--api : Generate richer API CRUD stubs (pagination, search, sorting)}';

    protected $description = 'Generate a full CRUD scaffold inside a module.';

    public function handle(ModuleScaffolder $scaffolder, ModuleRepository $repository): int
    {
        try {
            [$module, $resource] = $this->resolveModuleAndNameWithHint(
                'modular:crud User Product  OR  Product --module=User'
            );
            $this->requireModule($repository, $module);
            $created = $scaffolder->createCrud(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $resource,
                (bool) $this->option('api')
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->refreshModuleCache($repository);
        $this->info(sprintf('CRUD for [%s] created successfully in module [%s].', $resource, $module));
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
