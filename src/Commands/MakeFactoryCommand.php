<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeFactoryCommand extends Command
{
    protected $signature = 'modular:factory {target : Module name OR factory name} {name? : Factory name when module is first argument} {--module= : Module name when using name-first style} {--m= : Module name alias}';

    protected $description = 'Generate a model factory inside a module.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndName();
            $created = $scaffolder->createFactory(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $name
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Factory created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveModuleAndName(): array
    {
        $target = trim((string) $this->argument('target'));
        $nameArg = $this->argument('name');
        $name = is_string($nameArg) ? trim($nameArg) : '';
        $moduleOption = trim((string) ($this->option('module') ?: $this->option('m') ?: ''));

        if ($moduleOption !== '') {
            return [$moduleOption, $target];
        }

        if ($name === '') {
            throw new RuntimeException('Both module and factory name are required. Use: modular:factory User UserFactory  OR  UserFactory --module=User');
        }

        return [$target, $name];
    }
}
