<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\RefreshesModuleCache;
use Libinkk\Modular\Support\ModuleRepository;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeModuleCommand extends Command
{
    use RefreshesModuleCache;

    protected $signature = 'modular:make
        {name? : Module name, e.g. User}
        {--api : Create API routes only}
        {--web : Create web routes only}
        {--empty : Create folders and module.json only}
        {--minimal : Create provider, routes, and Controllers only}';

    protected $description = 'Create a new module with default structure and module.json.';

    public function handle(ModuleScaffolder $scaffolder, ModuleRepository $repository): int
    {
        try {
            $name = $this->resolveName();
            $options = $this->resolveOptions();
            $modulePath = $scaffolder->create(
                (string) config('modular.modules_path', base_path('Modules')),
                $name,
                $options
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->refreshModuleCache($repository);
        $this->info("Module [{$name}] created successfully.");
        $this->line("Path: {$modulePath}");

        return self::SUCCESS;
    }

    private function resolveName(): string
    {
        $name = trim((string) ($this->argument('name') ?? ''));
        if ($name === '') {
            $name = trim((string) $this->ask('Module Name?'));
        }
        if ($name === '') {
            throw new RuntimeException('Module name is required. Example: php artisan modular:make User');
        }

        return $name;
    }

    /**
     * @return array{api: bool, web: bool, empty: bool, minimal: bool}
     */
    private function resolveOptions(): array
    {
        $api = (bool) $this->option('api');
        $web = (bool) $this->option('web');
        $empty = (bool) $this->option('empty');
        $minimal = (bool) $this->option('minimal');

        if ($this->argument('name') === null || $this->argument('name') === '') {
            if (!$this->option('empty') && !$this->option('minimal') && !$this->option('api') && !$this->option('web')) {
                $api = (bool) $this->confirm('Create API routes?', true);
                $web = (bool) $this->confirm('Create web routes?', true);
                if ($api && $web) {
                    $api = false;
                    $web = false;
                } elseif (!$api && !$web) {
                    $web = true;
                }
            }
        }

        return compact('api', 'web', 'empty', 'minimal');
    }
}
