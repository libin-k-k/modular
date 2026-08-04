<?php

declare(strict_types=1);

namespace Libinkk\Modular\Scaffolding;

use Illuminate\Support\Str;

final class StubFactory
{
    public function render(string $type, string $namespace, string $className, string $moduleName = ''): string
    {
        return match ($type) {
            'controller' => $this->controllerTemplate($namespace, $className),
            'model' => $this->modelTemplate($namespace, $className, $moduleName),
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
            'trait' => $this->traitTemplate($namespace, $className),
            'helper' => $this->helperTemplate($namespace, $className),
            'command' => $this->consoleCommandTemplate($namespace, $className),
            default => $this->plainClassTemplate($namespace, $className),
        };
    }

    public function plainClassTemplate(string $namespace, string $className): string
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

    public function controllerTemplate(string $namespace, string $className): string
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

    public function modelTemplate(string $namespace, string $className, string $moduleName = ''): string
    {
        $factoryMethod = '';
        if ($moduleName !== '') {
            $factoryMethod = <<<PHP

    protected static function newFactory()
    {
        return \\Modules\\{$moduleName}\\Database\\Factories\\{$className}Factory::new();
    }

PHP;
        }

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
{$factoryMethod}
}

PHP;
    }

    public function serviceWithRepositoryTemplate(string $namespace, string $className, string $interfaceFqcn): string
    {
        $interfaceFqcn = ltrim($interfaceFqcn, '\\');
        $interfaceShort = class_basename($interfaceFqcn);

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use {$interfaceFqcn};

class {$className}
{
    public function __construct(
        private readonly {$interfaceShort} \$repository
    ) {
    }
}

PHP;
    }

    public function apiCrudControllerTemplate(string $moduleName, string $model): string
    {
        $var = Str::camel($model);

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Controllers;

use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\AnonymousResourceCollection;
use Illuminate\\Routing\\Controller;
use Modules\\{$moduleName}\\Requests\\Store{$model}Request;
use Modules\\{$moduleName}\\Requests\\Update{$model}Request;
use Modules\\{$moduleName}\\Resources\\{$model}Resource;
use Modules\\{$moduleName}\\Services\\{$model}Service;

class {$model}Controller extends Controller
{
    public function __construct(
        private readonly {$model}Service \$service
    ) {
    }

    public function index(Request \$request): AnonymousResourceCollection
    {
        return {$model}Resource::collection(
            \$this->service->paginate(
                search: \$request->string('search')->toString(),
                sort: \$request->string('sort')->toString() ?: 'id',
                direction: \$request->string('direction')->toString() ?: 'desc',
                perPage: (int) \$request->integer('per_page', 15)
            )
        );
    }

    public function store(Store{$model}Request \$request): JsonResponse
    {
        \${$var} = \$this->service->create(\$request->validated());

        return ({$model}Resource::make(\${$var}))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int|string \$id): {$model}Resource
    {
        return {$model}Resource::make(\$this->service->findOrFail(\$id));
    }

    public function update(Update{$model}Request \$request, int|string \$id): {$model}Resource
    {
        return {$model}Resource::make(
            \$this->service->update(\$id, \$request->validated())
        );
    }

    public function destroy(int|string \$id): JsonResponse
    {
        \$this->service->delete(\$id);

        return response()->json(null, 204);
    }
}

PHP;
    }

    public function apiCrudServiceTemplate(string $moduleName, string $model, string $interfaceName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Services;

use Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;
use Illuminate\\Database\\Eloquent\\Model;
use Modules\\{$moduleName}\\Interfaces\\{$interfaceName};

class {$model}Service
{
    public function __construct(
        private readonly {$interfaceName} \$repository
    ) {
    }

    public function paginate(string \$search = '', string \$sort = 'id', string \$direction = 'desc', int \$perPage = 15): LengthAwarePaginator
    {
        return \$this->repository->paginate(\$search, \$sort, \$direction, \$perPage);
    }

    public function findOrFail(int|string \$id): Model
    {
        return \$this->repository->findOrFail(\$id);
    }

    /**
     * @param array<string, mixed> \$data
     */
    public function create(array \$data): Model
    {
        return \$this->repository->create(\$data);
    }

    /**
     * @param array<string, mixed> \$data
     */
    public function update(int|string \$id, array \$data): Model
    {
        return \$this->repository->update(\$id, \$data);
    }

    public function delete(int|string \$id): bool
    {
        return \$this->repository->delete(\$id);
    }
}

PHP;
    }

    public function apiCrudRepositoryInterfaceTemplate(string $moduleName, string $interfaceName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Interfaces;

use Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;
use Illuminate\\Database\\Eloquent\\Model;

interface {$interfaceName}
{
    public function paginate(string \$search = '', string \$sort = 'id', string \$direction = 'desc', int \$perPage = 15): LengthAwarePaginator;

    public function findOrFail(int|string \$id): Model;

    /**
     * @param array<string, mixed> \$data
     */
    public function create(array \$data): Model;

    /**
     * @param array<string, mixed> \$data
     */
    public function update(int|string \$id, array \$data): Model;

    public function delete(int|string \$id): bool;
}

PHP;
    }

    public function apiCrudRepositoryTemplate(string $moduleName, string $model, string $interfaceName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Repositories;

use Illuminate\\Contracts\\Pagination\\LengthAwarePaginator;
use Illuminate\\Database\\Eloquent\\Model;
use Modules\\{$moduleName}\\Interfaces\\{$interfaceName};
use Modules\\{$moduleName}\\Models\\{$model};

class {$model}Repository implements {$interfaceName}
{
    public function paginate(string \$search = '', string \$sort = 'id', string \$direction = 'desc', int \$perPage = 15): LengthAwarePaginator
    {
        \$query = {$model}::query();

        if (\$search !== '') {
            \$query->where(function (\$builder) use (\$search): void {
                \$builder->where('id', 'like', "%{\$search}%");
            });
        }

        \$sort = in_array(\$sort, ['id', 'created_at', 'updated_at'], true) ? \$sort : 'id';
        \$direction = strtolower(\$direction) === 'asc' ? 'asc' : 'desc';

        return \$query->orderBy(\$sort, \$direction)->paginate(\$perPage);
    }

    public function findOrFail(int|string \$id): Model
    {
        return {$model}::query()->findOrFail(\$id);
    }

    public function create(array \$data): Model
    {
        return {$model}::query()->create(\$data);
    }

    public function update(int|string \$id, array \$data): Model
    {
        \$model = \$this->findOrFail(\$id);
        \$model->update(\$data);

        return \$model->refresh();
    }

    public function delete(int|string \$id): bool
    {
        return (bool) \$this->findOrFail(\$id)->delete();
    }
}

PHP;
    }

    public function apiCrudRequestTemplate(string $moduleName, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Requests;

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
            // 'name' => ['required', 'string', 'max:255'],
        ];
    }
}

PHP;
    }

    public function apiCrudResourceTemplate(string $moduleName, string $model): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Resources;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

class {$model}Resource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
        ];
    }
}

PHP;
    }

    public function apiCrudTestTemplate(string $moduleName, string $model): string
    {
        $route = Str::kebab(Str::pluralStudly($model));

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$moduleName}\\Tests;

use Tests\\TestCase;

class {$model}CrudTest extends TestCase
{
    public function test_it_lists_{$route}(): void
    {
        \$this->getJson('/api/{$route}')->assertSuccessful();
    }
}

PHP;
    }

    public function requestTemplate(string $namespace, string $className): string
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

    public function serviceTemplate(string $namespace, string $className): string
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

    public function repositoryTemplate(
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

    public function resourceTemplate(string $namespace, string $className): string
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

    public function eventTemplate(string $namespace, string $className): string
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

    public function listenerTemplate(string $namespace, string $className): string
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

    public function jobTemplate(string $namespace, string $className): string
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

    public function notificationTemplate(string $namespace, string $className): string
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

    public function policyTemplate(string $namespace, string $className): string
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

    public function middlewareTemplate(string $namespace, string $className): string
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

    public function dtoTemplate(string $namespace, string $className): string
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

    public function actionTemplate(string $namespace, string $className): string
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

    public function enumTemplate(string $namespace, string $className): string
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

    public function ruleTemplate(string $namespace, string $className): string
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

    public function testTemplate(string $namespace, string $className): string
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

    public function traitTemplate(string $namespace, string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

trait {$className}
{
    //
}

PHP;
    }

    public function helperTemplate(string $namespace, string $className): string
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

    public function consoleCommandTemplate(string $namespace, string $className): string
    {
        $signature = Str::kebab(str_replace('Command', '', $className));

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\\Console\\Command;

class {$className} extends Command
{
    protected \$signature = '{$signature}';

    protected \$description = 'Command description';

    public function handle(): int
    {
        //

        return self::SUCCESS;
    }
}

PHP;
    }

    public function webRoutesTemplate(string $moduleName): string
    {
        $moduleLower = strtolower($moduleName);

        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\\Support\\Facades\\Route;

/*
|--------------------------------------------------------------------------
| {$moduleName} Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module.
|
*/

Route::prefix('{$moduleLower}')->group(function (): void {
    // Route::get('/', [\\Modules\\{$moduleName}\\Controllers\\{$moduleName}Controller::class, 'index'])->name('{$moduleLower}.index');
});

PHP;
    }

    public function apiRoutesTemplate(string $moduleName): string
    {
        $moduleLower = strtolower($moduleName);

        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\\Support\\Facades\\Route;

/*
|--------------------------------------------------------------------------
| {$moduleName} API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module.
|
*/

Route::prefix('api/{$moduleLower}')->group(function (): void {
    // Route::apiResource('items', \\Modules\\{$moduleName}\\Controllers\\{$moduleName}Controller::class);
});

PHP;
    }

    public function configTemplate(string $moduleName, string $name = 'config'): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

return [
    'name' => '{$moduleName}',
    '{$name}' => [
        //
    ],
];

PHP;
    }

    public function langTemplate(string $moduleName, string $name = 'messages'): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

return [
    'module' => '{$moduleName}',
    '{$name}' => [
        'success' => 'Operation completed successfully.',
        'failed' => 'Operation failed.',
    ],
];

PHP;
    }

    public function viewTemplate(string $moduleName, string $viewName): string
    {
        return <<<BLADE
{{-- {$moduleName} :: {$viewName} --}}
<div>
    <h1>{$moduleName} / {$viewName}</h1>
    {{-- --}}
</div>

BLADE;
    }

    public function buildRepositoryInterfaceContent(string $namespace, string $interfaceName): string
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

    public function migrationTemplate(string $table): string
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

    public function factoryTemplate(string $moduleName, string $model): string
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

    public function seederTemplate(string $moduleName, string $model): string
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
