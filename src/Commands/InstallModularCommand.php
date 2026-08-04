<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Support\ModuleRepository;

class InstallModularCommand extends Command
{
    protected $signature = 'modular:install {--force : Overwrite published config if it already exists}';

    protected $description = 'Install modular: create Modules folder, publish config, cache, and verify (does not edit composer.json).';

    public function handle(Filesystem $files, ModuleRepository $repository): int
    {
        $modulesPath = (string) config('modular.modules_path', base_path('Modules'));
        $composerPath = base_path('composer.json');

        $this->info('Installing libinkk/modular...');

        $files->ensureDirectoryExists($modulesPath);
        $this->line("✔ Modules folder: {$modulesPath}");

        $this->call('vendor:publish', [
            '--tag' => 'modular-config',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->line('✔ Configuration published');

        $repository->cache();
        $this->line('✔ Module cache generated');

        $this->reportComposerAutoload($files, $composerPath);

        $this->newLine();
        $this->info('Installation complete.');
        $this->line('Next: php artisan modular:make User');

        return self::SUCCESS;
    }

    private function reportComposerAutoload(Filesystem $files, string $composerPath): void
    {
        if (!$files->exists($composerPath)) {
            $this->warn('composer.json not found — add Modules\\ PSR-4 manually in your app.');
            $this->printPsr4Hint();

            return;
        }

        $composer = json_decode($files->get($composerPath), true);
        $hasPsr4 = is_array($composer)
            && isset($composer['autoload']['psr-4']['Modules\\']);

        if ($hasPsr4) {
            $this->line('✔ Composer PSR-4 Modules\\ mapping already present');

            return;
        }

        $this->warn('Composer PSR-4 mapping for Modules\\ is not set (composer.json was not modified).');
        $this->printPsr4Hint();
        $this->line('Then run: composer dump-autoload');
    }

    private function printPsr4Hint(): void
    {
        $this->line('Add this to your application composer.json autoload.psr-4:');
        $this->line('  "Modules\\\\": "Modules/"');
    }
}
