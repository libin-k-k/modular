<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Unit;

use Libinkk\Modular\Support\ModuleIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ModuleIdentityTest extends TestCase
{
    public function test_accepts_valid_module_and_artifact_names(): void
    {
        $this->assertSame('Billing', ModuleIdentity::assertModuleName('Billing'));
        $this->assertSame('API/Admin/UserController', ModuleIdentity::assertArtifactName('API/Admin/UserController'));
        $this->assertSame('admin/dashboard', ModuleIdentity::assertResourcePath('admin/dashboard'));
    }

    #[DataProvider('dangerousModuleNames')]
    public function test_rejects_dangerous_module_names(string $name): void
    {
        $this->expectException(RuntimeException::class);
        ModuleIdentity::assertModuleName($name);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function dangerousModuleNames(): array
    {
        return [
            ['../Evil'],
            ['..\\Evil'],
            ['Foo/Bar'],
            ['Foo\\Bar'],
            ['/tmp/Evil'],
            ['Evil name'],
            ['Evil-Name'],
            ["Evil\0Name"],
            [''],
            ['   '],
        ];
    }

    #[DataProvider('dangerousArtifactNames')]
    public function test_rejects_dangerous_artifact_names(string $name): void
    {
        $this->expectException(RuntimeException::class);
        ModuleIdentity::assertArtifactName($name);
    }

    /**
     * @return list<array{0: string}>
     */
    public static function dangerousArtifactNames(): array
    {
        return [
            ['../EvilController'],
            ['Foo/../Bar'],
            ['Evil Controller'],
            ['Evil-Controller'],
            [''],
        ];
    }

    public function test_path_containment_rejects_escape(): void
    {
        $this->expectException(RuntimeException::class);
        ModuleIdentity::assertInsideModulesPath('/app/Modules', '/app/Evil');
    }

    public function test_module_path_stays_inside_root(): void
    {
        $path = ModuleIdentity::modulePath('/app/Modules', 'Orders');
        $this->assertSame('/app/Modules/Orders', $path);
    }
}
