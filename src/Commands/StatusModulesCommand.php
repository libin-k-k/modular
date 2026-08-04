<?php

declare(strict_types=1);

namespace Libinkk\Modular\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Providers\ModularServiceProvider;
use Libinkk\Modular\Support\ModuleRepository;

class StatusModulesCommand extends Command
{
    protected $signature = 'modular:status';

    protected $description = 'Show package and module system status for debugging.';

    public function handle(ModuleRepository $repository, Filesystem $files): int
    {
        $modulesPath = (string) config('modular.modules_path', base_path('Modules'));
        $cacheFile = (string) config('modular.cache_file', base_path('bootstrap/cache/modular_modules.php'));
        $modules = $repository->all();
        $enabled = $repository->enabled();

        $composer = base_path('composer.json');
        $hasPsr4 = false;
        if ($files->exists($composer)) {
            $decoded = json_decode($files->get($composer), true);
            $hasPsr4 = is_array($decoded)
                && isset($decoded['autoload']['psr-4']['Modules\\']);
        }

        $this->table(['Check', 'Status'], [
            ['Package Installed', class_exists(ModularServiceProvider::class) ? 'yes' : 'no'],
            ['Modules Path', $files->isDirectory($modulesPath) ? $modulesPath : 'missing'],
            ['Modules Loaded', (string) count($modules)],
            ['Enabled Modules', (string) count($enabled)],
            ['Discovery', $repository->hasCache() && (bool) config('modular.prefer_cache', true) ? 'cache' : 'scan'],
            ['Cache File', $repository->hasCache() ? 'present' : 'absent'],
            ['Cache Path', $cacheFile],
            ['Auto Refresh Cache', (bool) config('modular.auto_refresh_cache', true) ? 'on' : 'off'],
            ['Composer PSR-4 Modules\\', $hasPsr4 ? 'yes' : 'missing'],
            ['Config modular.php', $files->exists(config_path('modular.php')) ? 'published' : 'package default'],
            ['Providers Registered', (string) count($enabled)],
            ['Views Namespace', 'per enabled module (lowercase name)'],
            ['Translations Namespace', 'per enabled module (lowercase name)'],
            ['Migrations', 'loaded from each enabled module provider'],
        ]);

        return self::SUCCESS;
    }
}
