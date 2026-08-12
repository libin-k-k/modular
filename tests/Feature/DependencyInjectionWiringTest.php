<?php

declare(strict_types=1);

namespace Libinkk\Modular\Tests\Feature;

use Libinkk\Modular\Tests\Concerns\InteractsWithModules;
use Libinkk\Modular\Tests\TestCase;

class DependencyInjectionWiringTest extends TestCase
{
    use InteractsWithModules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootModuleWorkspace();
        $this->makeModule('Shop');
    }

    public function test_service_injects_into_existing_controller(): void
    {
        $this->artisan('modular:controller', ['target' => 'Shop', 'name' => 'ProductController', '--no-inject' => true])
            ->assertSuccessful();

        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'ProductService', '--inject' => true])
            ->expectsOutputToContain('Injected')
            ->assertSuccessful();

        $this->assertModuleFileContains(
            'Shop/Controllers/ProductController.php',
            'use Modules\\Shop\\Services\\ProductService;',
            'private readonly ProductService $service'
        );
    }

    public function test_service_prints_tip_when_controller_missing(): void
    {
        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'OrderService', '--inject' => true])
            ->expectsOutputToContain('Tip: no OrderController found')
            ->assertSuccessful();

        $this->assertFileExists($this->modulePath('Shop/Services/OrderService.php'));
        $this->assertFileDoesNotExist($this->modulePath('Shop/Controllers/OrderController.php'));
    }

    public function test_repository_merges_into_existing_empty_service(): void
    {
        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'ItemService', '--no-inject' => true])
            ->assertSuccessful();

        $this->artisan('modular:repository', ['target' => 'Shop', 'name' => 'ItemRepository', '--inject' => true])
            ->expectsOutputToContain('Injected')
            ->assertSuccessful();

        $this->assertModuleFileContains(
            'Shop/Services/ItemService.php',
            'use Modules\\Shop\\Interfaces\\ItemRepositoryInterface;',
            'private readonly ItemRepositoryInterface $repository'
        );
    }

    public function test_smart_merge_keeps_custom_methods(): void
    {
        $this->artisan('modular:controller', ['target' => 'Shop', 'name' => 'CartController', '--no-inject' => true])
            ->assertSuccessful();

        $path = $this->modulePath('Shop/Controllers/CartController.php');
        $content = $this->files->get($path);
        $content = str_replace(
            "{\n    //\n}",
            "{\n    public function index(): string\n    {\n        return 'ok';\n    }\n}",
            $content
        );
        $this->files->put($path, $content);

        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'CartService', '--inject' => true])
            ->assertSuccessful();

        $this->assertModuleFileContains(
            'Shop/Controllers/CartController.php',
            'private readonly CartService $service',
            'function index(): string',
            "return 'ok';"
        );
    }

    public function test_inject_is_idempotent(): void
    {
        $this->artisan('modular:controller', ['target' => 'Shop', 'name' => 'InvoiceController', '--no-inject' => true])
            ->assertSuccessful();
        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'InvoiceService', '--inject' => true])
            ->assertSuccessful();

        $before = $this->files->get($this->modulePath('Shop/Controllers/InvoiceController.php'));

        $injector = new \Libinkk\Modular\Support\ConstructorInjector($this->files);
        $changed = $injector->inject(
            $this->modulePath('Shop/Controllers/InvoiceController.php'),
            'Modules\\Shop\\Services\\InvoiceService',
            'service'
        );

        $this->assertFalse($changed);
        $this->assertSame($before, $this->files->get($this->modulePath('Shop/Controllers/InvoiceController.php')));
        $this->assertSame(1, substr_count($before, 'InvoiceService $service'));
    }

    public function test_no_inject_skips_controller_and_existing_service_patch(): void
    {
        $this->artisan('modular:controller', ['target' => 'Shop', 'name' => 'StockController', '--no-inject' => true])
            ->assertSuccessful();
        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'StockService', '--no-inject' => true])
            ->assertSuccessful();

        $serviceBefore = $this->files->get($this->modulePath('Shop/Services/StockService.php'));
        $controllerBefore = $this->files->get($this->modulePath('Shop/Controllers/StockController.php'));

        $this->artisan('modular:repository', [
            'target' => 'Shop',
            'name' => 'StockRepository',
            '--no-inject' => true,
        ])->assertSuccessful();

        $this->assertFileExists($this->modulePath('Shop/Repositories/StockRepository.php'));
        $this->assertFileExists($this->modulePath('Shop/Interfaces/StockRepositoryInterface.php'));
        $this->assertSame($serviceBefore, $this->files->get($this->modulePath('Shop/Services/StockService.php')));
        $this->assertSame($controllerBefore, $this->files->get($this->modulePath('Shop/Controllers/StockController.php')));
        $this->assertStringNotContainsString('StockRepositoryInterface', $serviceBefore);
    }

    public function test_controller_injects_existing_service_on_create(): void
    {
        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'PaymentService', '--no-inject' => true])
            ->assertSuccessful();

        $this->artisan('modular:controller', ['target' => 'Shop', 'name' => 'PaymentController', '--inject' => true])
            ->expectsOutputToContain('Injected')
            ->assertSuccessful();

        $this->assertModuleFileContains(
            'Shop/Controllers/PaymentController.php',
            'private readonly PaymentService $service'
        );
    }

    public function test_repository_still_creates_missing_service_with_no_inject(): void
    {
        $this->artisan('modular:repository', [
            'target' => 'Shop',
            'name' => 'ShipmentRepository',
            '--no-inject' => true,
        ])->assertSuccessful();

        $this->assertModuleFileContains(
            'Shop/Services/ShipmentService.php',
            'ShipmentRepositoryInterface $repository'
        );
        $this->assertFileDoesNotExist($this->modulePath('Shop/Controllers/ShipmentController.php'));
    }

    public function test_prompt_accepts_injection_when_no_flag(): void
    {
        $this->artisan('modular:controller', ['target' => 'Shop', 'name' => 'OfferController', '--no-inject' => true])
            ->assertSuccessful();

        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'OfferService'])
            ->expectsConfirmation('Wire matching dependencies into related classes?', 'yes')
            ->expectsOutputToContain('Injected')
            ->assertSuccessful();

        $this->assertModuleFileContains(
            'Shop/Controllers/OfferController.php',
            'private readonly OfferService $service'
        );
    }

    public function test_prompt_declines_injection_when_no_flag(): void
    {
        $this->artisan('modular:controller', ['target' => 'Shop', 'name' => 'DealController', '--no-inject' => true])
            ->assertSuccessful();

        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'DealService'])
            ->expectsConfirmation('Wire matching dependencies into related classes?', 'no')
            ->assertSuccessful();

        $content = $this->files->get($this->modulePath('Shop/Controllers/DealController.php'));
        $this->assertStringNotContainsString('DealService', $content);
    }

    public function test_inject_flag_skips_prompt(): void
    {
        $this->artisan('modular:service', ['target' => 'Shop', 'name' => 'QuoteService', '--inject' => true])
            ->expectsOutputToContain('Tip: no QuoteController found')
            ->assertSuccessful();

        $this->assertFileExists($this->modulePath('Shop/Services/QuoteService.php'));
    }
}
