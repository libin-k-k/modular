<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeRouteCommand extends Command
{
    protected $signature = 'modular:route {target : Module name OR route type (web|api|both)} {name? : Route type when module is first argument} {--module= : Module name} {--m= : Module name alias}';

    protected $description = 'Generate module route file(s) with default template.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $type] = $this->resolveModuleAndType();
            $created = $scaffolder->createRoute(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $type
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Route file(s) created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveModuleAndType(): array
    {
        $target = trim((string) $this->argument('target'));
        $nameArg = $this->argument('name');
        $name = is_string($nameArg) ? trim($nameArg) : '';
        $moduleOption = trim((string) ($this->option('module') ?: $this->option('m') ?: ''));

        if ($moduleOption !== '') {
            return [$moduleOption, $target !== '' ? $target : 'both'];
        }

        if ($name === '') {
            return [$target, 'both'];
        }

        return [$target, $name];
    }
}
