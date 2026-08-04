<?php

declare(strict_types=1);

namespace Libinkk\Modular\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
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
            'Middleware',
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

        $moduleLower = strtolower($moduleName);
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
        \$this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        if (file_exists(__DIR__ . '/../Routes/api.php')) {
            \$this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
        }

        \$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        \$this->loadViewsFrom(__DIR__ . '/../Views', '{$moduleLower}');
        \$this->loadTranslationsFrom(__DIR__ . '/../Lang', '{$moduleLower}');

        \$configPath = __DIR__ . '/../Config';
        if (is_dir(\$configPath)) {
            foreach (glob(\$configPath . '/*.php') ?: [] as \$configFile) {
                \$this->mergeConfigFrom(\$configFile, '{$moduleLower}.' . basename(\$configFile, '.php'));
            }
        }
    }
}

PHP;

        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Providers' . DIRECTORY_SEPARATOR . $providerClass . '.php', $providerContent);
        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n");
        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'api.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n");
        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php', "<?php\n\nreturn [\n    //\n];\n");
        $this->files->put($modulePath . DIRECTORY_SEPARATOR . 'module.json', $manifestJson . PHP_EOL);

        return $modulePath;
    }

    /**
     * @return list<string>
     */
    public function createCrud(string $modulesPath, string $moduleName, string $resourceName): array
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
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'controller', $model . 'Controller'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'request', 'Store' . $model . 'Request'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'request', 'Update' . $model . 'Request'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'service', $model . 'Service'));
        $created = array_merge($created, $this->createArtifact($modulesPath, $moduleName, 'repository', $model . 'Repository'));
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

        $this->appendCrudRoutes($modulePath, $moduleName, $model, $route);
        $created[] = $modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'api.php';

        return $created;
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

        $this->files->put($filePath, $this->migrationTemplate($table));

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

        $this->files->put($filePath, $this->factoryTemplate($moduleName, $model));

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

        $this->files->put($filePath, $this->seederTemplate($moduleName, Str::beforeLast($className, 'Seeder') ?: $className));

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
                $this->files->put($interfacePath, $this->buildRepositoryInterfaceContent($interfaceNamespace, $interfaceName));
                $created[] = $interfacePath;
            }

            $this->files->put($filePath, $this->repositoryTemplate($namespace, $className, $interfaceNamespace, $interfaceName));
            $created[] = $filePath;

            $this->bindRepositoryInProvider(
                $modulePath,
                $moduleName,
                $interfaceNamespace . '\\' . $interfaceName,
                $namespace . '\\' . $className
            );

            return $created;
        }

        $content = $this->buildClassContent($type, $namespace, $className, $moduleName);
        $this->files->put($filePath, $content);
        $created[] = $filePath;

        return $created;
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

    private function buildClassContent(string $type, string $namespace, string $className, string $moduleName = ''): string
    {
        return match ($type) {
            'controller' => $this->controllerTemplate($namespace, $className),
            'model' => $this->modelTemplate($namespace, $className),
            'request' => $this->requestTemplate($namespace, $className),
            'service' => $this->serviceTemplate($namespace, $className),
            'resource' => $this->resourceTemplate($namespace, $className),
            'event' => $this->eventTemplate($namespace, $className),
            'listener' => $this->listenerTemplate($namespace, $className),
            'job' => $this->jobTemplate($namespace, $className),
            'notification' => $this->notificationTemplate($namespace, $className),
            'policy' => $this->policyTemplate($namespace, $className),
            'middleware' => $this->middlewareTemplate($namespace, $className),
            'dto' => $this->dtoTemplate($namespace, $className),
            'action' => $this->actionTemplate($namespace, $className),
            'enum' => $this->enumTemplate($namespace, $className),
            'rule' => $this->ruleTemplate($namespace, $className),
            'test' => $this->testTemplate($namespace, $className),
            default => $this->plainClassTemplate($namespace, $className),
        };
    }

    private function plainClassTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    //
}

PHP;
    }

    private function controllerTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Routing\\Controller;

class {$className} extends Controller
{
    //
}

PHP;
    }

    private function modelTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

class {$className} extends Model
{
    use HasFactory;

    protected \$guarded = [];

    //
}

PHP;
    }

    private function requestTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Foundation\\Http\\FormRequest;

class {$className} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}

PHP;
    }

    private function serviceTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    //
}

PHP;
    }

    private function repositoryTemplate(
        string $namespace,
        string $className,
        string $interfaceNamespace,
        string $interfaceName
    ): string {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use {$interfaceNamespace}\\{$interfaceName};

class {$className} implements {$interfaceName}
{
    //
}

PHP;
    }

    private function resourceTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

class {$className} extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
            //
        ];
    }
}

PHP;
    }

    private function eventTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Foundation\\Events\\Dispatchable;
use Illuminate\\Queue\\SerializesModels;

class {$className}
{
    use Dispatchable;
    use SerializesModels;

    //
}

PHP;
    }

    private function listenerTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    public function handle(object \$event): void
    {
        //
    }
}

PHP;
    }

    private function jobTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Bus\\Queueable;
use Illuminate\\Contracts\\Queue\\ShouldQueue;
use Illuminate\\Foundation\\Bus\\Dispatchable;
use Illuminate\\Queue\\InteractsWithQueue;
use Illuminate\\Queue\\SerializesModels;

class {$className} implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        //
    }
}

PHP;
    }

    private function notificationTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Bus\\Queueable;
use Illuminate\\Notifications\\Notification;

class {$className} extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object \$notifiable): array
    {
        return ['mail'];
    }

    //
}

PHP;
    }

    private function policyTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Auth\\Access\\HandlesAuthorization;

class {$className}
{
    use HandlesAuthorization;

    //
}

PHP;
    }

    private function middlewareTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class {$className}
{
    public function handle(Request \$request, Closure \$next): Response
    {
        //

        return \$next(\$request);
    }
}

PHP;
    }

    private function dtoTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    /**
     * @param array<string, mixed> \$data
     */
    public static function fromArray(array \$data): self
    {
        return new self();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            //
        ];
    }
}

PHP;
    }

    private function actionTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

class {$className}
{
    public function handle(): mixed
    {
        //
    }
}

PHP;
    }

    private function enumTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

enum {$className}: string
{
    case DEFAULT = 'default';
}

PHP;
    }

    private function ruleTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Closure;
use Illuminate\\Contracts\\Validation\\ValidationRule;

class {$className} implements ValidationRule
{
    public function validate(string \$attribute, mixed \$value, Closure \$fail): void
    {
        //
    }
}

PHP;
    }

    private function testTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use PHPUnit\\Framework\\TestCase;

class {$className} extends TestCase
{
    public function test_example(): void
    {
        \$this->assertTrue(true);
    }
}

PHP;
    }

    private function buildRepositoryInterfaceContent(string $namespace, string $interfaceName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

interface {$interfaceName}
{
    //
}

PHP;
    }

    private function migrationTemplate(string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table): void {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};

PHP;
    }

    private function factoryTemplate(string $moduleName, string $model): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Database\\Factories;

use Illuminate\\Database\\Eloquent\\Factories\\Factory;
use Modules\\{$moduleName}\\Models\\{$model};

/**
 * @extends Factory<{$model}>
 */
class {$model}Factory extends Factory
{
    protected \$model = {$model}::class;

    public function definition(): array
    {
        return [
            //
        ];
    }
}

PHP;
    }

    private function seederTemplate(string $moduleName, string $model): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Database\\Seeders;

use Illuminate\\Database\\Seeder;

class {$model}Seeder extends Seeder
{
    public function run(): void
    {
        //
    }
}

PHP;
    }
}
