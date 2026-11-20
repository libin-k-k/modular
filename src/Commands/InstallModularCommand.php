<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Support\ModuleRepository;
use Symfony\Component\Process\Process;

class InstallModularCommand extends Command
{
    protected $signature = 'modular:install
        {--force : Overwrite published config if it already exists}
        {--no-composer : Skip writing Modules PSR-4 into composer.json}
        {--no-dump : Write composer.json but skip composer dump-autoload}';

    protected $description = 'Install modular: create Modules folder, publish config, register Composer PSR-4, cache, and verify.';

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

        if ($this->option('no-composer')) {
            $this->warn('Skipped Composer PSR-4 update (--no-composer).');
            $this->printPsr4Hint($this->relativeModulesAutoloadPath($modulesPath));
        } else {
            $this->ensureComposerAutoload($files, $composerPath, $modulesPath);
        }

        $this->newLine();
        $this->info('Installation complete.');
        $this->line('Next: php artisan modular:make User');

        return self::SUCCESS;
    }

    private function ensureComposerAutoload(Filesystem $files, string $composerPath, string $modulesPath): void
    {
        if (!$files->exists($composerPath)) {
            $this->warn('composer.json not found. Add Modules\\ PSR-4 manually in your app.');
            $this->printPsr4Hint($this->relativeModulesAutoloadPath($modulesPath));

            return;
        }

        $raw = $files->get($composerPath);
        $composer = json_decode($raw, true);
        if (!is_array($composer)) {
            $this->error('composer.json is invalid JSON. Fix it, then re-run modular:install.');
            $this->printPsr4Hint($this->relativeModulesAutoloadPath($modulesPath));

            return;
        }

        $autoloadPath = $this->relativeModulesAutoloadPath($modulesPath);
        $existing = $composer['autoload']['psr-4']['Modules\\'] ?? null;

        if (is_string($existing) && rtrim(str_replace('\\', '/', $existing), '/') . '/' === $autoloadPath) {
            $this->line('✔ Composer PSR-4 Modules\\ mapping already present');
            $this->dumpAutoloadIfNeeded();

            return;
        }

        $composer['autoload'] ??= [];
        if (!is_array($composer['autoload'])) {
            $this->error('composer.json autoload must be an object/array.');

            return;
        }

        $composer['autoload']['psr-4'] ??= [];
        if (!is_array($composer['autoload']['psr-4'])) {
            $this->error('composer.json autoload.psr-4 must be an object/array.');

            return;
        }

        $composer['autoload']['psr-4']['Modules\\'] = $autoloadPath;

        $encoded = json_encode(
            $composer,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        if ($encoded === false) {
            $this->error('Could not encode composer.json.');

            return;
        }

        $files->put($composerPath, $encoded . PHP_EOL);
        $this->line("✔ Composer PSR-4 added: \"Modules\\\\\": \"{$autoloadPath}\"");

        $this->dumpAutoloadIfNeeded();
    }

    private function dumpAutoloadIfNeeded(): void
    {
        if ($this->option('no-dump')) {
            $this->line('Skipped composer dump-autoload (--no-dump). Run it manually when ready.');

            return;
        }

        $process = new Process(['composer', 'dump-autoload'], base_path());
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful()) {
            $this->line('✔ composer dump-autoload completed');

            return;
        }

        $this->warn('composer dump-autoload failed. Run it manually:');
        $this->line('  composer dump-autoload');
        $output = trim($process->getErrorOutput() ?: $process->getOutput());
        if ($output !== '') {
            $this->line($output);
        }
    }

    private function relativeModulesAutoloadPath(string $modulesPath): string
    {
        $base = str_replace('\\', '/', rtrim(base_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $normalized = str_replace('\\', '/', rtrim($modulesPath, DIRECTORY_SEPARATOR)) . '/';

        if (str_starts_with($normalized, $base)) {
            return substr($normalized, strlen($base));
        }

        return 'Modules/';
    }

    private function printPsr4Hint(string $autoloadPath): void
    {
        $this->line('Add this to your application composer.json autoload.psr-4:');
        $this->line('  "Modules\\\\": "' . $autoloadPath . '"');
        $this->line('Then run: composer dump-autoload');
    }
}
