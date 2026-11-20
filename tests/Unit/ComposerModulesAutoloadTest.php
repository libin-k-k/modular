<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Libinkk\Modular\Support\ComposerModulesAutoload;
use PHPUnit\Framework\TestCase;

class ComposerModulesAutoloadTest extends TestCase
{
    private Filesystem $files;
    private string $composerPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
        $dir = sys_get_temp_dir() . '/modular_composer_' . uniqid('', true);
        $this->files->ensureDirectoryExists($dir);
        $this->composerPath = $dir . '/composer.json';
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory(dirname($this->composerPath));
        parent::tearDown();
    }

    public function test_adds_modules_mapping_without_touching_other_keys(): void
    {
        $this->files->put($this->composerPath, json_encode([
            'name' => 'app/demo',
            'require' => ['php' => '^8.2'],
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                ],
                'files' => ['helpers.php'],
            ],
            'scripts' => ['test' => 'phpunit'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        $result = (new ComposerModulesAutoload($this->files))->ensure($this->composerPath, 'Modules');

        $this->assertTrue($result['changed']);
        $decoded = json_decode($this->files->get($this->composerPath), true);
        $this->assertSame('Modules/', $decoded['autoload']['psr-4']['Modules\\']);
        $this->assertSame('app/', $decoded['autoload']['psr-4']['App\\']);
        $this->assertSame(['helpers.php'], $decoded['autoload']['files']);
        $this->assertSame(['php' => '^8.2'], $decoded['require']);
        $this->assertSame(['test' => 'phpunit'], $decoded['scripts']);
        $this->assertSame('app/demo', $decoded['name']);
    }

    public function test_is_idempotent_when_mapping_exists(): void
    {
        $this->files->put($this->composerPath, json_encode([
            'autoload' => [
                'psr-4' => [
                    'Modules\\' => 'Modules/',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        $result = (new ComposerModulesAutoload($this->files))->ensure($this->composerPath, 'Modules/');

        $this->assertFalse($result['changed']);
    }
}
