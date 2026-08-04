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
            $this->artisan($command, ['module' => $module, 'name' => $name])
                ->assertSuccessful();
            $this->assertFileExists($this->modulesPath . $path);
        }

        $this->assertFileExists($this->modulesPath . '/Blog/Interfaces/PostRepositoryInterface.php');
    }
}
