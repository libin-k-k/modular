<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Tests\TestCase;

class ArtifactCommandsTest extends TestCase
{
    private string $modulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesPath = (string) config('modular.modules_path');
        $files = new Filesystem();
        $files->deleteDirectory(dirname($this->modulesPath) . DIRECTORY_SEPARATOR . 'bootstrap');
        $files->deleteDirectory($this->modulesPath);
        $files->ensureDirectoryExists($this->modulesPath);

        $this->artisan('modular:make', ['name' => 'Blog'])->assertSuccessful();
    }

    public function test_it_generates_all_readme_listed_artifacts(): void
    {
        $cases = [
            ['modular:controller', 'Blog', 'PostController', '/Blog/Controllers/PostController.php'],
            ['modular:model', 'Blog', 'Post', '/Blog/Models/Post.php'],
            ['modular:request', 'Blog', 'StorePostRequest', '/Blog/Requests/StorePostRequest.php'],
            ['modular:service', 'Blog', 'PostService', '/Blog/Services/PostService.php'],
            ['modular:repository', 'Blog', 'PostRepository', '/Blog/Repositories/PostRepository.php'],
            ['modular:resource', 'Blog', 'PostResource', '/Blog/Resources/PostResource.php'],
            ['modular:event', 'Blog', 'PostCreated', '/Blog/Events/PostCreated.php'],
            ['modular:listener', 'Blog', 'SendPostNotification', '/Blog/Listeners/SendPostNotification.php'],
            ['modular:job', 'Blog', 'SyncPostJob', '/Blog/Jobs/SyncPostJob.php'],
            ['modular:notification', 'Blog', 'PostPublishedNotification', '/Blog/Notifications/PostPublishedNotification.php'],
            ['modular:policy', 'Blog', 'PostPolicy', '/Blog/Policies/PostPolicy.php'],
            ['modular:middleware', 'Blog', 'AdminMiddleware', '/Blog/Middleware/AdminMiddleware.php'],
            ['modular:dto', 'Blog', 'PostData', '/Blog/DTO/PostData.php'],
            ['modular:action', 'Blog', 'CreatePost', '/Blog/Actions/CreatePost.php'],
            ['modular:enum', 'Blog', 'PostStatus', '/Blog/Enums/PostStatus.php'],
            ['modular:rule', 'Blog', 'ValidSlugRule', '/Blog/Rules/ValidSlugRule.php'],
            ['modular:test', 'Blog', 'PostTest', '/Blog/Tests/PostTest.php'],
        ];

        foreach ($cases as [$command, $module, $name, $path]) {
            $this->artisan($command, ['target' => $module, 'name' => $name])
                ->assertSuccessful();
            $this->assertFileExists($this->modulesPath . $path);
        }

        $this->assertFileExists($this->modulesPath . '/Blog/Interfaces/PostRepositoryInterface.php');

        $provider = file_get_contents($this->modulesPath . '/Blog/Providers/BlogServiceProvider.php') ?: '';
        $this->assertStringContainsString('PostRepositoryInterface::class', $provider);
        $this->assertStringContainsString('PostRepository::class', $provider);
    }

    public function test_all_generators_support_module_option_styles(): void
    {
        $this->artisan('modular:controller', ['target' => 'API/UserController', '--module' => 'Blog'])
            ->assertSuccessful();
        $this->assertFileExists($this->modulesPath . '/Blog/Controllers/API/UserController.php');

        $this->artisan('modular:model', ['target' => 'API/AdminModel', '--m' => 'Blog'])
            ->assertSuccessful();
        $this->assertFileExists($this->modulesPath . '/Blog/Models/API/AdminModel.php');

        $this->artisan('modular:service', ['target' => 'Nested/PostService', '--module' => 'Blog'])
            ->assertSuccessful();
        $this->assertFileExists($this->modulesPath . '/Blog/Services/Nested/PostService.php');
    }

    public function test_it_generates_full_crud_scaffold(): void
    {
        $this->artisan('modular:crud', ['target' => 'Blog', 'name' => 'Product'])
            ->assertSuccessful();

        $expected = [
            '/Blog/Models/Product.php',
            '/Blog/Controllers/ProductController.php',
            '/Blog/Requests/StoreProductRequest.php',
            '/Blog/Requests/UpdateProductRequest.php',
            '/Blog/Services/ProductService.php',
            '/Blog/Repositories/ProductRepository.php',
            '/Blog/Interfaces/ProductRepositoryInterface.php',
            '/Blog/DTO/ProductData.php',
            '/Blog/Actions/CreateProduct.php',
            '/Blog/Actions/UpdateProduct.php',
            '/Blog/Actions/DeleteProduct.php',
            '/Blog/Resources/ProductResource.php',
            '/Blog/Policies/ProductPolicy.php',
            '/Blog/Tests/ProductCrudTest.php',
            '/Blog/Database/Factories/ProductFactory.php',
            '/Blog/Database/Seeders/ProductSeeder.php',
        ];

        foreach ($expected as $path) {
            $this->assertFileExists($this->modulesPath . $path);
        }

        $migrations = glob($this->modulesPath . '/Blog/Database/Migrations/*_create_products_table.php') ?: [];
        $this->assertNotEmpty($migrations);

        $apiRoutes = file_get_contents($this->modulesPath . '/Blog/Routes/api.php') ?: '';
        $this->assertStringContainsString("apiResource('products'", $apiRoutes);

        $provider = file_get_contents($this->modulesPath . '/Blog/Providers/BlogServiceProvider.php') ?: '';
        $this->assertStringContainsString('ProductRepositoryInterface::class', $provider);
    }

    public function test_crud_supports_module_option_style(): void
    {
        $this->artisan('modular:crud', ['target' => 'Category', '--module' => 'Blog'])
            ->assertSuccessful();

        $this->assertFileExists($this->modulesPath . '/Blog/Models/Category.php');
        $this->assertFileExists($this->modulesPath . '/Blog/Controllers/CategoryController.php');
    }

    public function test_migration_factory_seeder_commands_and_default_stubs(): void
    {
        $this->artisan('modular:migration', ['target' => 'Blog', 'name' => 'create_posts_table'])
            ->assertSuccessful();
        $migrations = glob($this->modulesPath . '/Blog/Database/Migrations/*_create_posts_table.php') ?: [];
        $this->assertNotEmpty($migrations);
        $migrationContent = file_get_contents($migrations[0]) ?: '';
        $this->assertStringContainsString("Schema::create('posts'", $migrationContent);

        $this->artisan('modular:factory', ['target' => 'PostFactory', '--module' => 'Blog'])
            ->assertSuccessful();
        $factoryPath = $this->modulesPath . '/Blog/Database/Factories/PostFactory.php';
        $this->assertFileExists($factoryPath);
        $factoryContent = file_get_contents($factoryPath) ?: '';
        $this->assertStringContainsString('extends Factory', $factoryContent);
        $this->assertStringContainsString('definition(): array', $factoryContent);

        $this->artisan('modular:seeder', ['target' => 'Blog', 'name' => 'PostSeeder'])
            ->assertSuccessful();
        $seederPath = $this->modulesPath . '/Blog/Database/Seeders/PostSeeder.php';
        $this->assertFileExists($seederPath);
        $seederContent = file_get_contents($seederPath) ?: '';
        $this->assertStringContainsString('extends Seeder', $seederContent);
        $this->assertStringContainsString('public function run(): void', $seederContent);

        $this->artisan('modular:controller', ['target' => 'StubController', '--module' => 'Blog'])
            ->assertSuccessful();
        $controller = file_get_contents($this->modulesPath . '/Blog/Controllers/StubController.php') ?: '';
        $this->assertStringContainsString('extends Controller', $controller);
        $this->assertStringContainsString('use Illuminate\\Http\\Request;', $controller);
        $this->assertStringContainsString('//', $controller);

        $this->artisan('modular:request', ['target' => 'StubRequest', '--module' => 'Blog'])
            ->assertSuccessful();
        $request = file_get_contents($this->modulesPath . '/Blog/Requests/StubRequest.php') ?: '';
        $this->assertStringContainsString('extends FormRequest', $request);
        $this->assertStringContainsString('public function rules(): array', $request);
    }
}
