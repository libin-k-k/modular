<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Support\Str;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeModelCommand extends BaseMakeArtifactCommand
{
    protected $signature = 'modular:model
        {target : Module name OR class path/name}
        {name? : Class path/name when module is first argument}
        {--module= : Module name when using name-first style}
        {--m= : Module name alias}
        {--migration : Create a new migration file for the model}';

    protected $description = 'Generate a model inside a module.';

    protected function artifactType(): string
    {
        return 'model';
    }

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndName();
            $modulesPath = (string) config('modular.modules_path', base_path('Modules'));
            $created = $scaffolder->createArtifact($modulesPath, $module, 'model', $name)['paths'];

            if ($this->option('migration')) {
                $model = Str::studly(Str::singular(basename(str_replace('\\', '/', $name))));
                $table = Str::snake(Str::pluralStudly($model));
                $created = array_merge(
                    $created,
                    $scaffolder->createMigration($modulesPath, $module, 'create_' . $table . '_table')
                );
            }
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Model created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
