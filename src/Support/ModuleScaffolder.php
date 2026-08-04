<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class ModuleScaffolder
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    public function create(string $modulesPath, string $moduleName): string
    {
        $modulePath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        if ($this->files->exists($modulePath)) {
            throw new RuntimeException(sprintf('Module [%s] already exists.', $moduleName));
        }

        $directories = [
            'Controllers',
            'Requests',
            'Models',
            'Services',
            'Repositories',
            'Interfaces',
            'Actions',
            'DTO',
            'Traits',
            'Enums',
            'Policies',
            'Rules',
            'Events',
            'Listeners',
            'Jobs',
            'Notifications',
            'Resources',
            'Helpers',
            'Console',
            'Database/Migrations',
            'Database/Seeders',
            'Database/Factories',
            'Config',
            'Routes',
            'Views',
            'Lang',
            'Tests',
            'Providers',
        ];

        foreach ($directories as $directory) {
            $this->files->ensureDirectoryExists($modulePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory));
        }

        $manifest = [
            'name' => $moduleName,
            'description' => "{$moduleName} Module",
            'version' => '1.0.0',
            'enabled' => true,
            'dependencies' => [],
        ];

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($manifestJson === false) {
            throw new RuntimeException(sprintf('Could not generate module.json for [%s].', $moduleName));
        }

        $providerClass = $moduleName . 'ServiceProvider';
        $providerContent = <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$providerClass} extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}

PHP;

        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $providerClass . '.php', $providerContent);
        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'web.php', "<?php\n\n");
        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'api.php', "<?php\n\n");
        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'module.json', $manifestJson . PHP_EOL);

        return $modulePath;
    }
}
