<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

abstract class BaseMakeArtifactCommand extends Command
{
    abstract protected function artifactType(): string;

    public function handle(ModuleScaffolder $scaffolder): int
    {
        $module = trim((string) $this->argument('module'));
        $name = trim((string) $this->argument('name'));

        if ($module === '' || $name === '') {
            $this->error('Both module and name are required.');

            return self::FAILURE;
        }

        try {
            $created = $scaffolder->createArtifact(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $this->artifactType(),
                $name
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('%s created successfully.', ucfirst($this->artifactType())));
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
