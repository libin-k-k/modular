<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Libinkk\Modular\Commands\Concerns\ResolvesModuleArguments;
use Libinkk\Modular\Support\ModuleScaffolder;
use RuntimeException;

class MakeLangCommand extends Command
{
    use ResolvesModuleArguments;

    protected $signature = 'modular:lang {target : Module name OR lang file name} {name? : Lang file name when module is first} {--module= : Module name} {--m= : Module name alias} {--locale=en : Locale folder}';

    protected $description = 'Generate a module language file with default template.';

    public function handle(ModuleScaffolder $scaffolder): int
    {
        try {
            [$module, $name] = $this->resolveModuleAndNameWithHint(
                'modular:lang User messages  OR  messages --module=User'
            );
            $created = $scaffolder->createLang(
                (string) config('modular.modules_path', base_path('Modules')),
                $module,
                $name,
                (string) $this->option('locale')
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Lang file created successfully.');
        foreach ($created as $path) {
            $this->line($path);
        }

        return self::SUCCESS;
    }
}
