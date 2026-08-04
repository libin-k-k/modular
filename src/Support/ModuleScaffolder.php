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
        $content = $this->buildClassContent($type, $namespace, $className);
        $this->files->put($filePath, $content);

        $created = [$filePath];

        if ($type === 'repository') {
            $interfaceDir = $modulePath . DIRECTORY_SEPARATOR . 'Interfaces' . DIRECTORY_SEPARATOR . $nestedPath;
            $this->files->ensureDirectoryExists($interfaceDir);
            $interfaceName = str_ends_with($className, 'Repository') ? $className . 'Interface' : $className . 'RepositoryInterface';
            $interfaceNamespace = implode('\\', array_filter(array_merge(['Modules', $moduleName, 'Interfaces'], $parts), static fn (string $value): bool => $value !== ''));
            $interfacePath = $interfaceDir . $interfaceName . '.php';
            $this->files->put($interfacePath, $this->buildRepositoryInterfaceContent($interfaceNamespace, $interfaceName));
            $created[] = $interfacePath;
        }

        return $created;
    }

    private function buildClassContent(string $type, string $namespace, string $className): string
    {
        return match ($type) {
            'controller' => $this->controllerTemplate($namespace, $className),
            'model' => $this->modelTemplate($namespace, $className),
            'enum' => $this->enumTemplate($namespace, $className),
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
use Illuminate\\Routing\\Controller;

class {$className} extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => '{$className} index',
        ]);
    }
}

PHP;
    }

    private function modelTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Database\\Eloquent\\Model;

class {$className} extends Model
{
    protected \$guarded = [];
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

    private function testTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use PHPUnit\\Framework\\TestCase;

class {$className} extends TestCase
{
    public function test_placeholder(): void
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
}

PHP;
    }
}
