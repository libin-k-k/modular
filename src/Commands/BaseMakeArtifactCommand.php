<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\ResolvesModuleArguments;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

abstract class BaseMakeArtifactCommand extends Command
{
    use ResolvesModuleArguments;

    abstract protected function artifactType(): string;

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveModuleAndName(): array
    {
        return $this->resolveModuleAndNameWithHint(
            'modular:' . $this->artifactType() . ' Module Name  OR  Name --module=Module'
        );
    }

    protected function shouldInjectDependencies(): bool
    {
        return true;
    }

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndName();
            $modulesPath = (string) config('modular.modules_path', base_path('Modules'));
            $modulePath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $module;
            if (! is_dir($modulePath)) {
                throw new RuntimeException(sprintf('Module [%s] does not exist.', $module));
            }

            $result = $scaffolder->createArtifact(
                $modulesPath,
                $module,
                $this->artifactType(),
                $name,
                $this->shouldInjectDependencies()
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('%s created successfully.', ucfirst($this->artifactType())));
        foreach ($result['paths'] as $path) {
            $this->line($path);
        }
        foreach ($result['notices'] as $notice) {
            $this->comment($notice);
        }

        return self::SUCCESS;
    }
}
