<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Tests\Concerns\InteractsWithModules;
use Libinkk\Modular\Tests\TestCase;

class GeneratorTest extends TestCase
{
    use InteractsWithModules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootModuleWorkspace();
        $this->makeModule('App');
    }

    public function test_make_creates_module_templates_and_rejects_duplicates(): void
    {
        foreach ([
            'Controllers', 'Models', 'Services', 'Repositories', 'Routes', 'Providers',
            'Config', 'Views', 'Lang', 'Database/Migrations', 'Middleware',
        ] as $dir) {
            $this->assertDirectoryExists($this->modulePath("App/{$dir}"));
        }

        $this->assertModuleFileContains('App/module.json', '"name": "App"');
        $this->assertModuleFileContains('App/Routes/web.php', 'App Web Routes', "Route::prefix('app')");
        $this->assertModuleFileContains('App/Routes/api.php', 'App API Routes', "Route::prefix('api/app')");
        $this->assertModuleFileContains('App/Providers/AppServiceProvider.php', 'loadMigrationsFrom', 'loadViewsFrom');
        $this->assertModuleFileContains('App/Lang/en/messages.php', "'module' => 'App'");
        $this->assertModuleFileContains('App/Views/index.blade.php', 'App / index');
        $this->assertModuleFileContains('App/Config/config.php', "'name' => 'App'");

        $this->artisan('modular:make', ['name' => 'App'])->assertFailed();
    }

    public function test_class_generators_create_default_stubs(): void
    {
        $cases = [
            ['modular:controller', 'PostController', 'App/Controllers/PostController.php', ['extends Controller', 'Request']],
            ['modular:model', 'Post', 'App/Models/Post.php', ['extends Model', 'HasFactory']],
            ['modular:request', 'StorePostRequest', 'App/Requests/StorePostRequest.php', ['extends FormRequest', 'rules(): array']],
            ['modular:service', 'PostService', 'App/Services/PostService.php', ['class PostService']],
            ['modular:repository', 'PostRepository', 'App/Repositories/PostRepository.php', ['implements PostRepositoryInterface']],
            ['modular:resource', 'PostResource', 'App/Resources/PostResource.php', ['extends JsonResource']],
            ['modular:event', 'PostCreated', 'App/Events/PostCreated.php', ['Dispatchable']],
            ['modular:listener', 'SendPostMail', 'App/Listeners/SendPostMail.php', ['function handle']],
            ['modular:job', 'SyncPostJob', 'App/Jobs/SyncPostJob.php', ['ShouldQueue']],
            ['modular:notification', 'PostReady', 'App/Notifications/PostReady.php', ['extends Notification']],
            ['modular:policy', 'PostPolicy', 'App/Policies/PostPolicy.php', ['HandlesAuthorization']],
            ['modular:middleware', 'EnsurePost', 'App/Middleware/EnsurePost.php', ['function handle']],
            ['modular:dto', 'PostData', 'App/DTO/PostData.php', ['fromArray', 'toArray']],
            ['modular:action', 'CreatePost', 'App/Actions/CreatePost.php', ['function handle']],
            ['modular:enum', 'PostStatus', 'App/Enums/PostStatus.php', ['enum PostStatus']],
            ['modular:rule', 'ValidSlug', 'App/Rules/ValidSlug.php', ['ValidationRule']],
            ['modular:test', 'PostTest', 'App/Tests/PostTest.php', ['extends TestCase']],
            ['modular:trait', 'HasSlug', 'App/Traits/HasSlug.php', ['trait HasSlug']],
            ['modular:helper', 'PostHelper', 'App/Helpers/PostHelper.php', ['class PostHelper']],
            ['modular:command', 'SyncPostsCommand', 'App/Console/SyncPostsCommand.php', ['extends Command', 'sync-posts']],
        ];

        foreach ($cases as [$command, $name, $path, $needles]) {
            $this->artisan($command, ['target' => 'App', 'name' => $name])->assertSuccessful();
            $this->assertModuleFileContains($path, ...$needles);
        }

        $this->assertFileExists($this->modulePath('App/Interfaces/PostRepositoryInterface.php'));
        $this->assertModuleFileContains(
            'App/Providers/AppServiceProvider.php',
            'PostRepositoryInterface::class',
            'PostRepository::class'
        );
    }

    public function test_database_and_resource_file_generators(): void
    {
        $this->artisan('modular:migration', ['target' => 'App', 'name' => 'create_posts_table'])->assertSuccessful();
        $this->assertNotEmpty(glob($this->modulePath('App/Database/Migrations/*_create_posts_table.php')) ?: []);

        $this->artisan('modular:factory', ['target' => 'PostFactory', '--module' => 'App'])->assertSuccessful();
        $this->assertModuleFileContains('App/Database/Factories/PostFactory.php', 'extends Factory', 'definition(): array');

        $this->artisan('modular:seeder', ['target' => 'App', 'name' => 'PostSeeder'])->assertSuccessful();
        $this->assertModuleFileContains('App/Database/Seeders/PostSeeder.php', 'extends Seeder', 'function run');

        $this->artisan('modular:config', ['target' => 'settings', '--module' => 'App'])->assertSuccessful();
        $this->assertModuleFileContains('App/Config/settings.php', "'name' => 'App'");

        $this->artisan('modular:lang', ['target' => 'App', 'name' => 'validation'])->assertSuccessful();
        $this->assertModuleFileContains('App/Lang/en/validation.php', "'module' => 'App'");

        $this->artisan('modular:view', ['target' => 'admin/dashboard', '--m' => 'App'])->assertSuccessful();
        $this->assertModuleFileContains('App/Views/admin/dashboard.blade.php', 'App / dashboard');

        $this->files->delete($this->modulePath('App/Routes/web.php'));
        $this->files->delete($this->modulePath('App/Routes/api.php'));
        $this->artisan('modular:route', ['target' => 'App', 'name' => 'both'])->assertSuccessful();
        $this->assertModuleFileContains('App/Routes/web.php', 'App Web Routes');
        $this->assertModuleFileContains('App/Routes/api.php', 'App API Routes');
    }

    public function test_multi_style_flags_and_crud_scaffold(): void
    {
        $this->artisan('modular:controller', ['target' => 'API/V1/ItemController', '--module' => 'App'])->assertSuccessful();
        $this->assertFileExists($this->modulePath('App/Controllers/API/V1/ItemController.php'));

        $this->artisan('modular:model', ['target' => 'Item', '--m' => 'App'])->assertSuccessful();
        $this->assertFileExists($this->modulePath('App/Models/Item.php'));

        $this->artisan('modular:crud', ['target' => 'Product', '--module' => 'App'])->assertSuccessful();

        foreach ([
            'Models/Product.php',
            'Controllers/ProductController.php',
            'Requests/StoreProductRequest.php',
            'Requests/UpdateProductRequest.php',
            'Services/ProductService.php',
            'Repositories/ProductRepository.php',
            'Interfaces/ProductRepositoryInterface.php',
            'DTO/ProductData.php',
            'Actions/CreateProduct.php',
            'Actions/UpdateProduct.php',
            'Actions/DeleteProduct.php',
            'Resources/ProductResource.php',
            'Policies/ProductPolicy.php',
            'Tests/ProductCrudTest.php',
            'Database/Factories/ProductFactory.php',
            'Database/Seeders/ProductSeeder.php',
        ] as $relative) {
            $this->assertFileExists($this->modulePath("App/{$relative}"));
        }

        $this->assertNotEmpty(glob($this->modulePath('App/Database/Migrations/*_create_products_table.php')) ?: []);
        $this->assertModuleFileContains('App/Routes/api.php', "apiResource('products'");
    }

    public function test_generators_fail_when_module_is_missing(): void
    {
        $this->artisan('modular:controller', ['target' => 'Missing', 'name' => 'X'])->assertFailed();
        $this->artisan('modular:migration', ['target' => 'Missing', 'name' => 'create_x_table'])->assertFailed();
        $this->artisan('modular:crud', ['target' => 'Missing', 'name' => 'Thing'])->assertFailed();
    }
}
