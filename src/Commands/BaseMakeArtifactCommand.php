<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

abstract class BaseMakeArtifactCommand extends Command
{
    abstract protected function artifactType(): string;

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveModuleAndName(): array
    {
        $target = trim((string) $this->argument('target'));
        $nameArg = $this->argument('name');
        $name = is_string($nameArg) ? trim($nameArg) : '';
        $moduleOption = trim((string) ($this->option('module') ?: $this->option('m') ?: ''));

        if ($target === '') {
            throw new RuntimeException('Target is required.');
        }

        if ($moduleOption !== '') {
            return [$moduleOption, $target];
        }

        if ($name === '') {
            throw new RuntimeException('Both module and name are required. Use: modular:' . $this->artifactType() . ' Module Name  OR  Name --module=Module');
        }

        return [$target, $name];
    }

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndName();
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
