<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Libinkk\Modular\Scaffolding\StubFactory;
use RuntimeException;

class ModuleScaffolder
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly StubFactory $stubs = new StubFactory(),
    ) {
    }

    public function create(string $modulesPath, string $moduleName, array $options = []): string
    {
        $modulePath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        if ($this->files->exists($modulePath)) {
            throw new RuntimeException(sprintf('Module [%s] already exists.', $moduleName));
        }

        $empty = (bool) ($options['empty'] ?? false);
        $minimal = (bool) ($options['minimal'] ?? false);
        $apiOnly = (bool) ($options['api'] ?? false);
        $webOnly = (bool) ($options['web'] ?? false);

        if ($apiOnly && $webOnly) {
            throw new RuntimeException('Use either --api or --web, not both.');
        }

        $directories = $empty
            ? [
                'Controllers', 'Requests', 'Models', 'Services', 'Repositories', 'Interfaces',
                'Actions', 'DTO', 'Traits', 'Enums', 'Policies', 'Rules', 'Events', 'Listeners',
                'Jobs', 'Notifications', 'Resources', 'Helpers', 'Console', 'Database/Migrations',
                'Database/Seeders', 'Database/Factories', 'Config', 'Routes', 'Views', 'Lang',
                'Tests', 'Providers', 'Middleware',
            ]
            : ($minimal
                ? ['Controllers', 'Routes', 'Providers']
                : [
                    'Controllers', 'Requests', 'Models', 'Services', 'Repositories', 'Interfaces',
                    'Actions', 'DTO', 'Traits', 'Enums', 'Policies', 'Rules', 'Events', 'Listeners',
                    'Jobs', 'Notifications', 'Resources', 'Helpers', 'Console', 'Database/Migrations',
                    'Database/Seeders', 'Database/Factories', 'Config', 'Routes', 'Views', 'Lang',
                    'Tests', 'Providers', 'Middleware',
                ]);

        foreach ($directories as $directory) {
            $this->files->ensureDirectoryExists($modulePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory));
        }

        $manifest = [
            'name' => $moduleName,
            'description' => "{$moduleName} Module",
            'author' => '',
            'website' => '',
            'license' => 'MIT',
            'priority' => 100,
            'version' => '1.0.0',
            'enabled' => true,
            'dependencies' => [],
            'providers' => [],
            'aliases' => [],
        ];

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($manifestJson === false) {
            throw new RuntimeException(sprintf('Could not generate module.json for [%s].', $moduleName));
        }

        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'module.json', $manifestJson . PHP_EOL);

        if ($empty) {
            return $modulePath;
        }

        $withWeb = !$apiOnly;
        $withApi = !$webOnly;
        if ($minimal) {
            $withWeb = !$apiOnly;
            $withApi = !$webOnly;
        }

        $this->writeModuleProvider($modulePath, $moduleName, $withWeb, $withApi, !$minimal);

        if ($withWeb) {
            $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'web.php', $this->stubs->webRoutesTemplate($moduleName));
        }
        if ($withApi) {
            $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'api.php', $this->stubs->apiRoutesTemplate($moduleName));
        }

        if (!$minimal) {
            $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php', $this->stubs->configTemplate($moduleName));
            $this->files->ensureDirectoryExists($modulePath . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . 'en');
            $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . 'messages.php', $this->stubs->langTemplate($moduleName));
            $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'index.blade.php', $this->stubs->viewTemplate($moduleName, 'index'));
        }

        return $modulePath;
    }

    private function writeModuleProvider(
        string $modulePath,
        string $moduleName,
        bool $withWeb,
        bool $withApi,
        bool $withExtras
    ): void {
        $moduleLower = strtolower($moduleName);
        $providerClass = $moduleName . 'ServiceProvider';

        $routeBoot = '';
        if ($withWeb) {
            $routeBoot .= "        \$this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');\n\n";
        }
        if ($withApi) {
            $routeBoot .= "        if (file_exists(__DIR__ . '/../Routes/api.php')) {\n            \$this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');\n        }\n\n";
        }

        $extraBoot = '';
        if ($withExtras) {
            $extraBoot = <<<PHP
        \$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        \$this->loadViewsFrom(__DIR__ . '/../Views', '{$moduleLower}');
        \$this->loadTranslationsFrom(__DIR__ . '/../Lang', '{$moduleLower}');

        \$configPath = __DIR__ . '/../Config';
        if (is_dir(\$configPath)) {
            foreach (glob(\$configPath . '/*.php') ?: [] as \$configFile) {
                \$this->mergeConfigFrom(\$configFile, '{$moduleLower}.' . basename(\$configFile, '.php'));
            }
        }
PHP;
        } else {
            $extraBoot = "        // Minimal module — add migrations/views/lang/config as needed.";
        }

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
{$routeBoot}{$extraBoot}
    }
}

PHP;

        $this->files->put(
            $modulePath . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $providerClass . '.php',
            $providerContent
        );
    }

    /**
     * @return list<string>
     */
    public function createRoute(string $modulesPath, string $moduleName, string $type = 'web'): array
    {
        $modulePath = $this->assertModuleExists($modulesPath, $moduleName);
        $type = strtolower(trim($type));
        if (!in_array($type, ['web', 'api', 'both'], true)) {
            throw new RuntimeException('Route type must be web, api, or both.');
        }

        $created = [];
        $routesDir = $modulePath . DIRECTORY_SEPARATOR . 'Routes';
        $this->files->ensureDirectoryExists($routesDir);

        if ($type === 'web' || $type === 'both') {
            $path = $routesDir . DIRECTORY_SEPARATOR . 'web.php';
            if ($this->files->exists($path)) {
                throw new RuntimeException(sprintf('Route file [%s] already exists.', 'web.php'));
            }
            $this->files->put($path, $this->stubs->webRoutesTemplate($moduleName));
            $created[] = $path;
        }

        if ($type === 'api' || $type === 'both') {
            $path = $routesDir . DIRECTORY_SEPARATOR . 'api.php';
            if ($this->files->exists($path)) {
                throw new RuntimeException(sprintf('Route file [%s] already exists.', 'api.php'));
            }
            $this->files->put($path, $this->stubs->apiRoutesTemplate($moduleName));
            $created[] = $path;
        }

        return $created;
    }

    /**
     * @return list<string>
     */
    public function createConfig(string $modulesPath, string $moduleName, string $name): array
    {
        $modulePath = $this->assertModuleExists($modulesPath, $moduleName);
        $name = Str::snake(basename(trim(str_replace(['\\', '.php'], ['/', ''], $name), '/')));
        if ($name === '') {
            throw new RuntimeException('Config name is required.');
        }

        $dir = $modulePath . DIRECTORY_SEPARATOR . 'Config';
        $this->files->ensureDirectoryExists($dir);
        $path = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        if ($this->files->exists($path)) {
            throw new RuntimeException(sprintf('Config [%s] already exists.', $name));
        }

        $this->files->put($path, $this->stubs->configTemplate($moduleName, $name));

        return [$path];
    }

    /**
     * @return list<string>
     */
    public function createLang(string $modulesPath, string $moduleName, string $name, string $locale = 'en'): array
    {
        $modulePath = $this->assertModuleExists($modulesPath, $moduleName);
        $name = Str::snake(basename(trim(str_replace(['\\', '.php'], ['/', ''], $name), '/')));
        $locale = trim($locale) !== '' ? trim($locale) : 'en';
        if ($name === '') {
            throw new RuntimeException('Lang file name is required.');
        }

        $dir = $modulePath . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $locale;
        $this->files->ensureDirectoryExists($dir);
        $path = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        if ($this->files->exists($path)) {
            throw new RuntimeException(sprintf('Lang file [%s/%s] already exists.', $locale, $name));
        }

        $this->files->put($path, $this->stubs->langTemplate($moduleName, $name));

        return [$path];
    }

    /**
     * @return list<string>
     */
    public function createView(string $modulesPath, string $moduleName, string $name): array
    {
        $modulePath = $this->assertModuleExists($modulesPath, $moduleName);
        $name = trim(str_replace('\\', '/', $name), '/');
        if ($name === '') {
            throw new RuntimeException('View name is required.');
        }

        $name = str_ends_with($name, '.blade.php') ? substr($name, 0, -10) : $name;
        $parts = array_values(array_filter(explode('/', $name), static fn (string $part): bool => $part !== ''));
        $viewName = array_pop($parts) ?: 'index';
        $nested = $parts !== [] ? implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR : '';
        $dir = $modulePath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $nested;
        $this->files->ensureDirectoryExists($dir);
        $path = $dir . $viewName . '.blade.php';
        if ($this->files->exists($path)) {
            throw new RuntimeException(sprintf('View [%s] already exists.', $name));
        }

        $this->files->put($path, $this->stubs->viewTemplate($moduleName, $viewName));

        return [$path];
    }

    /**
     * @return list<string>
     */
    public function createCrud(string $modulesPath, string $moduleName, string $resourceName, bool $api = false): array
    {
        $resourceName = trim($resourceName);
        if ($resourceName === '') {
            throw new RuntimeException('Resource name is required.');
        }

        $modulePath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        if (!$this->files->isDirectory($modulePath)) {
            throw new RuntimeException(sprintf('Module [%s] does not exist.', $moduleName));
        }

        $model = Str::studly(Str::singular($resourceName));
        $table = Str::snake(Str::pluralStudly($model));
        $route = Str::kebab(Str::pluralStudly($model));
        $created = [];

        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'model', $model));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'repository', $model . 'Repository'));
        // Service is created by repository generator with constructor DI when missing.
        if (!$this->files->exists($modulePath . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $model . 'Service.php')) {
            $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'service', $model . 'Service'));
        }
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'controller', $model . 'Controller'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'request', 'Store' . $model . 'Request'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'request', 'Update' . $model . 'Request'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'dto', $model . 'Data'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'action', 'Create' . $model));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'action', 'Update' . $model));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'action', 'Delete' . $model));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'resource', $model . 'Resource'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'policy', $model . 'Policy'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'test', $model . 'CrudTest'));
        $created = array_merge($created, $this->createMigration($modulesPath, $moduleName, 'create_' . $table . '_table'));
        $created = array_merge($created, $this->createFactory($modulesPath, $moduleName, $model . 'Factory'));
        $created = array_merge($created, $this->createSeeder($modulesPath, $moduleName, $model . 'Seeder'));

        if ($api) {
            $this->applyApiCrudStubs($modulePath, $moduleName, $model);
        }

        $this->appendCrudRoutes($modulePath, $moduleName, $model, $route);
        $created[] = $modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'api.php';

        return $created;
    }

    private function applyApiCrudStubs(string $modulePath, string $moduleName, string $model): void
    {
        $interfaceName = $model . 'RepositoryInterface';
        $replacements = [
            'Controllers' . DIRECTORY_SEPARATOR . $model . 'Controller.php' => $this->stubs->apiCrudControllerTemplate($moduleName, $model),
            'Services' . DIRECTORY_SEPARATOR . $model . 'Service.php' => $this->stubs->apiCrudServiceTemplate($moduleName, $model, $interfaceName),
            'Repositories' . DIRECTORY_SEPARATOR . $model . 'Repository.php' => $this->stubs->apiCrudRepositoryTemplate($moduleName, $model, $interfaceName),
            'Interfaces' . DIRECTORY_SEPARATOR . $interfaceName . '.php' => $this->stubs->apiCrudRepositoryInterfaceTemplate($moduleName, $interfaceName),
            'Requests' . DIRECTORY_SEPARATOR . 'Store' . $model . 'Request.php' => $this->stubs->apiCrudRequestTemplate($moduleName, 'Store' . $model . 'Request'),
            'Requests' . DIRECTORY_SEPARATOR . 'Update' . $model . 'Request.php' => $this->stubs->apiCrudRequestTemplate($moduleName, 'Update' . $model . 'Request'),
            'Resources' . DIRECTORY_SEPARATOR . $model . 'Resource.php' => $this->stubs->apiCrudResourceTemplate($moduleName, $model),
            'Tests' . DIRECTORY_SEPARATOR . $model . 'CrudTest.php' => $this->stubs->apiCrudTestTemplate($moduleName, $model),
        ];

        foreach ($replacements as $relative => $content) {
            $path = $modulePath . DIRECTORY_SEPARATOR . $relative;
            $this->files->put($path, $content);
        }
    }

    /**
     * @return list<string>
     */
    public function createMigration(string $modulesPath, string $moduleName, string $name): array
    {
        $modulePath = $this->assertModuleExists($modulesPath, $moduleName);
        $name = trim(str_replace(['\\', '.php'], ['/', ''], $name), '/');
        if ($name === '') {
            throw new RuntimeException('Migration name is required.');
        }

        $base = Str::snake(basename($name));
        if (!str_starts_with($base, 'create_') && !str_contains($base, '_table')) {
            $base = 'create_' . Str::snake(Str::pluralStudly(Str::studly($base))) . '_table';
        }

        $table = 'table';
        if (preg_match('/create_(.+)_table$/', $base, $matches) === 1) {
            $table = $matches[1];
        }

        $fileName = date('Y_m_d_His') . '_' . $base . '.php';
        $targetDir = $modulePath . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
        $this->files->ensureDirectoryExists($targetDir);
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        if ($this->files->exists($filePath)) {
            throw new RuntimeException(sprintf('Migration [%s] already exists.', $fileName));
        }

        $this->files->put($filePath, $this->stubs->migrationTemplate($table));

        return [$filePath];
    }

    /**
     * @return list<string>
     */
    public function createFactory(string $modulesPath, string $moduleName, string $name): array
    {
        $modulePath = $this->assertModuleExists($modulesPath, $moduleName);
        $name = trim(str_replace(['\\', '.php'], ['/', ''], $name), '/');
        if ($name === '') {
            throw new RuntimeException('Factory name is required.');
        }

        $className = Str::studly(basename($name));
        if (!str_ends_with($className, 'Factory')) {
            $className .= 'Factory';
        }
        $model = Str::beforeLast($className, 'Factory') ?: $className;

        $targetDir = $modulePath . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Factories';
        $this->files->ensureDirectoryExists($targetDir);
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $className . '.php';

        if ($this->files->exists($filePath)) {
            throw new RuntimeException(sprintf('Factory [%s] already exists.', $className));
        }

        $this->files->put($filePath, $this->stubs->factoryTemplate($moduleName, $model));

        return [$filePath];
    }

    /**
     * @return list<string>
     */
    public function createSeeder(string $modulesPath, string $moduleName, string $name): array
    {
        $modulePath = $this->assertModuleExists($modulesPath, $moduleName);
        $name = trim(str_replace(['\\', '.php'], ['/', ''], $name), '/');
        if ($name === '') {
            throw new RuntimeException('Seeder name is required.');
        }

        $className = Str::studly(basename($name));
        if (!str_ends_with($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        $targetDir = $modulePath . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Seeders';
        $this->files->ensureDirectoryExists($targetDir);
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $className . '.php';

        if ($this->files->exists($filePath)) {
            throw new RuntimeException(sprintf('Seeder [%s] already exists.', $className));
        }

        $this->files->put($filePath, $this->stubs->seederTemplate($moduleName, Str::beforeLast($className, 'Seeder') ?: $className));

        return [$filePath];
    }

    private function assertModuleExists(string $modulesPath, string $moduleName): string
    {
        $modulePath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        if (!$this->files->isDirectory($modulePath)) {
            throw new RuntimeException(sprintf('Module [%s] does not exist.', $moduleName));
        }

        return $modulePath;
    }

    /**
     * @return list<string> created file paths
     */
    public function createArtifact(string $modulesPath, string $moduleName, string $type, string $name): array
    {
        $modulePath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        if (!$this->files->isDirectory($modulePath)) {
            throw new RuntimeException(sprintf('Module [%s] does not exist.', $moduleName));
        }

        $name = trim(str_replace('\\', '/', $name), '/');
        if ($name === '') {
            throw new RuntimeException('Artifact name is required.');
        }

        $map = [
            'controller' => 'Controllers',
            'model' => 'Models',
            'request' => 'Requests',
            'service' => 'Services',
            'repository' => 'Repositories',
            'resource' => 'Resources',
            'event' => 'Events',
            'listener' => 'Listeners',
            'job' => 'Jobs',
            'notification' => 'Notifications',
            'policy' => 'Policies',
            'middleware' => 'Middleware',
            'dto' => 'DTO',
            'action' => 'Actions',
            'enum' => 'Enums',
            'rule' => 'Rules',
            'test' => 'Tests',
            'trait' => 'Traits',
            'helper' => 'Helpers',
            'command' => 'Console',
        ];

        if (!isset($map[$type])) {
            throw new RuntimeException(sprintf('Unsupported artifact type [%s].', $type));
        }

        $relativeDir = $map[$type];
        $parts = array_values(array_filter(explode('/', $name), static fn (string $part): bool => $part !== ''));
        if ($parts === []) {
            throw new RuntimeException('Artifact name is invalid.');
        }

        $className = array_pop($parts);
        $nestedPath = $parts !== [] ? implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR : '';
        $targetDir = $modulePath . DIRECTORY_SEPARATOR . $relativeDir . DIRECTORY_SEPARATOR . $nestedPath;
        $this->files->ensureDirectoryExists($targetDir);

        $filePath = $targetDir . $className . '.php';
        if ($this->files->exists($filePath)) {
            throw new RuntimeException(sprintf('%s [%s] already exists.', ucfirst($type), $name));
        }

        $namespaceParts = array_merge(['Modules', $moduleName], explode('/', str_replace('\\', '/', $relativeDir)), $parts);
        $namespace = implode('\\', array_filter($namespaceParts, static fn (string $value): bool => $value !== ''));

        $created = [];

        if ($type === 'repository') {
            $interfaceDir = $modulePath . DIRECTORY_SEPARATOR . 'Interfaces' . DIRECTORY_SEPARATOR . $nestedPath;
            $this->files->ensureDirectoryExists($interfaceDir);
            $interfaceName = str_ends_with($className, 'Repository') ? $className . 'Interface' : $className . 'RepositoryInterface';
            $interfaceNamespace = implode('\\', array_filter(array_merge(['Modules', $moduleName, 'Interfaces'], $parts), static fn (string $value): bool => $value !== ''));
            $interfacePath = $interfaceDir . $interfaceName . '.php';
            if (!$this->files->exists($interfacePath)) {
                $this->files->put($interfacePath, $this->stubs->buildRepositoryInterfaceContent($interfaceNamespace, $interfaceName));
                $created[] = $interfacePath;
            }

            $this->files->put($filePath, $this->stubs->repositoryTemplate($namespace, $className, $interfaceNamespace, $interfaceName));
            $created[] = $filePath;

            $this->bindRepositoryInProvider(
                $modulePath,
                $moduleName,
                $interfaceNamespace . '\\' . $interfaceName,
                $namespace . '\\' . $className
            );

            $serviceBase = str_ends_with($className, 'Repository')
                ? substr($className, 0, -strlen('Repository'))
                : $className;
            if ($serviceBase !== '') {
                $serviceName = implode('/', array_merge($parts, [$serviceBase . 'Service']));
                $servicePath = $modulePath . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR
                    . ($nestedPath !== '' ? $nestedPath : '')
                    . $serviceBase . 'Service.php';
                if (!$this->files->exists($servicePath)) {
                    $serviceCreated = $this->createServiceWithRepository(
                        $modulesPath,
                        $moduleName,
                        $serviceName,
                        $interfaceNamespace . '\\' . $interfaceName
                    );
                    $created = array_merge($created, $serviceCreated);
                }
            }

            return $created;
        }

        $content = $this->stubs->render($type, $namespace, $className, $moduleName);
        $this->files->put($filePath, $content);
        $created[] = $filePath;

        return $created;
    }

    /**
     * @return list<string>
     */
    private function createServiceWithRepository(
        string $modulesPath,
        string $moduleName,
        string $serviceName,
        string $interfaceFqcn
    ): array {
        $modulePath = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $moduleName;
        $name = trim(str_replace('\\', '/', $serviceName), '/');
        $parts = array_values(array_filter(explode('/', $name), static fn (string $part): bool => $part !== ''));
        $className = array_pop($parts);
        if ($className === null || $className === '') {
            return [];
        }

        $nestedPath = $parts !== [] ? implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR : '';
        $targetDir = $modulePath . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $nestedPath;
        $this->files->ensureDirectoryExists($targetDir);
        $filePath = $targetDir . $className . '.php';
        if ($this->files->exists($filePath)) {
            return [];
        }

        $namespace = implode('\\', array_filter(
            array_merge(['Modules', $moduleName, 'Services'], $parts),
            static fn (string $value): bool => $value !== ''
        ));

        $this->files->put(
            $filePath,
            $this->stubs->serviceWithRepositoryTemplate($namespace, $className, $interfaceFqcn)
        );

        return [$filePath];
    }

    private function bindRepositoryInProvider(
        string $modulePath,
        string $moduleName,
        string $interfaceFqcn,
        string $repositoryFqcn
    ): void {
        $providerPath = $modulePath . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $moduleName . 'ServiceProvider.php';
        if (!$this->files->exists($providerPath)) {
            return;
        }

        $content = $this->files->get($providerPath);
        $bindLine = sprintf(
            "        \$this->app->bind(\\%s::class, \\%s::class);",
            ltrim($interfaceFqcn, '\\'),
            ltrim($repositoryFqcn, '\\')
        );

        if (str_contains($content, $bindLine) || str_contains($content, $interfaceFqcn . '::class')) {
            return;
        }

        if (preg_match('/public function register\(\): void\s*\{\s*\}/s', $content) === 1) {
            $content = preg_replace(
                '/public function register\(\): void\s*\{\s*\}/s',
                "public function register(): void\n    {\n{$bindLine}\n    }",
                $content,
                1
            );
        } elseif (preg_match('/public function register\(\): void\s*\{/', $content) === 1) {
            $content = preg_replace(
                '/(public function register\(\): void\s*\{)/',
                "$1\n{$bindLine}",
                $content,
                1
            );
        }

        if (!is_string($content)) {
            throw new RuntimeException(sprintf('Unable to bind repository in [%s].', $providerPath));
        }

        $this->files->put($providerPath, $content);
    }

    private function appendCrudRoutes(string $modulePath, string $moduleName, string $model, string $route): void
    {
        $apiPath = $modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'api.php';
        $controller = sprintf('\\Modules\\%s\\Controllers\\%sController::class', $moduleName, $model);
        $snippet = <<<PHP

Route::apiResource('{$route}', {$controller});

PHP;
        $existing = $this->files->exists($apiPath) ? $this->files->get($apiPath) : "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";
        if (!str_contains($existing, "apiResource('{$route}'")) {
            $this->files->put($apiPath, rtrim($existing) . $snippet);
        }
    }
}
