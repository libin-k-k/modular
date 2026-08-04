<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Unit;

use Libinkk\Modular\Support\Module;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function test_it_maps_manifest_array_to_module_object(): void
    {
        $module = Module::fromArray([
            'name' => 'Orders',
            'description' => 'Orders module',
            'version' => '2.1.0',
            'enabled' => false,
            'dependencies' => ['Users', '', null, 'Products'],
        ], '/tmp/Modules/Orders');

        $this->assertSame('Orders', $module->name);
        $this->assertSame('/tmp/Modules/Orders', $module->path);
        $this->assertSame('Orders module', $module->description);
        $this->assertSame('2.1.0', $module->version);
        $this->assertFalse($module->enabled);
        $this->assertSame(['Users', 'Products'], $module->dependencies);

        $array = $module->toArray();
        $this->assertSame('Orders', $array['name']);
        $this->assertSame(['Users', 'Products'], $array['dependencies']);
    }

    public function test_it_uses_defaults_when_manifest_fields_missing(): void
    {
        $module = Module::fromArray([], '/tmp/Modules/Fallback');

        $this->assertSame('Fallback', $module->name);
        $this->assertSame('', $module->description);
        $this->assertSame('1.0.0', $module->version);
        $this->assertTrue($module->enabled);
        $this->assertSame([], $module->dependencies);
    }
}
