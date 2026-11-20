<p align="center">
  <img src="docs/images/modular-banner.jpg" alt="libinkk/modular - Enterprise Laravel Modular Architecture" width="100%">
</p>

# libinkk/modular

**Enterprise-grade Laravel modular architecture. Zero manual registration.**

Build large Laravel apps as **business modules** under `Modules/`. The package discovers modules, registers providers, loads routes, migrations, views, lang, and config, scaffolds layered code, and lets you enable, disable, rename, or delete features safely.

```bash
composer require libinkk/modular
php artisan modular:install
# add "Modules\\": "Modules/" to composer.json autoload, then:
composer dump-autoload
php artisan modular:make Catalog
php artisan modular:crud Catalog Product --api
```

| | |
| --- | --- |
| **Package** | `libinkk/modular` |
| **License** | MIT |
| **PHP** | `^8.1` (tested through **8.6**) |
| **Laravel** | `10` / `11` / `12` / `13` |
| **Developer** | Libin K K |
| **Email** | [libinkk1999@gmail.com](mailto:libinkk1999@gmail.com) |
| **Website** | [www.libinkk.in](https://www.libinkk.in) |
| **Docs** | [DOCUMENTATION.md](DOCUMENTATION.md) / [libinkk.in](https://www.libinkk.in) |

---

## Table of contents

1. [Why this package?](#why-this-package)
2. [Features](#features)
3. [Requirements](#requirements)
4. [Installation (step by step)](#installation-step-by-step)
5. [Full tutorial: Blog Post API](#full-tutorial-blog-post-api)
6. [Full tutorial: Catalog Product feature](#full-tutorial-catalog-product-feature)
7. [Feature walkthroughs](#feature-walkthroughs)
8. [Module structure](#module-structure)
9. [Architecture](#architecture)
10. [Artisan commands](#artisan-commands)
11. [Configuration](#configuration)
12. [Benchmarks](#benchmarks)
13. [Testing](#testing)
14. [CI](#ci)
15. [Best practices](#best-practices)
16. [Troubleshooting](#troubleshooting)
17. [FAQ](#faq)
18. [Roadmap](#roadmap)
19. [License](#license)

---

## Why this package?

Growing Laravel apps often end up with:

- Flat, mixed folders under `app/`
- Manual provider / route / migration registration
- Fat controllers with business logic
- No clean way to turn a feature off
- Slow onboarding because every project invents its own structure

`libinkk/modular` enforces **one opinionated module layout** and automates discovery so you spend time on business rules, not boilerplate.

| Pain | Solution |
| --- | --- |
| Messy `app/` | One folder per business domain under `Modules/` |
| Manual providers | Auto-discovered and registered |
| Manual routes / migrations / views | Loaded from each module automatically |
| Fat controllers | Generators push Request → DTO → Action → Service → Repository |
| No feature switch | `modular:enable` / `modular:disable` |
| Unclear deps | `module.json` dependencies + `modular:doctor` |
| Slow scaffolding | `modular:make` + `modular:crud` |
| Boot scan cost | Module cache (`prefer_cache`) |

---

## Features

- Modular architecture under `Modules/`
- Automatic discovery of enabled modules
- Automatic service provider registration
- Auto-load `Routes/web.php` + `Routes/api.php`
- Auto-load migrations, views, translations, config
- Repository pattern with interface binding
- Service layer, DTO, Action generators
- Events, listeners, jobs, policies, notifications, middleware
- Full CRUD scaffold (including `--api`)
- Smart constructor DI wiring (`--inject` / `--no-inject`)
- Enable / disable / rename / delete modules
- Module list, info, status, doctor
- Module dependency checks
- Production module cache + auto-refresh
- PHPUnit suite + GitHub Actions matrix (PHP 8.1 to 8.6)

---

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | `^8.1` (CI covers `8.1` to `8.6`) |
| Laravel / Illuminate | `^10` / `^11` / `^12` / `^13` |
| Composer | 2.x |

> Laravel 13 typically needs PHP `^8.3`. Match your app PHP version accordingly. PHP **8.6.0** is the current latest stable ([php.net release notes](https://www.php.net/releases/8_6_0.php)).

---

## Installation (step by step)

### Step 1: Require the package

```bash
composer require libinkk/modular
```

### Step 2: Run install

```bash
php artisan modular:install
```

What this does:

1. Creates the `Modules/` directory
2. Publishes `config/modular.php`
3. Generates the module cache file

It does **not** edit your app `composer.json`.

### Step 3: Add PSR-4 autoload

In your application `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Modules\\": "Modules/"
    }
  }
}
```

### Step 4: Dump autoload

```bash
composer dump-autoload
```

### Step 5: Create your first module

```bash
php artisan modular:make User
```

### Step 6: Verify health

```bash
php artisan modular:list
php artisan modular:status
php artisan modular:doctor
```

You are ready to generate code inside `Modules/User/`.

---

## Full tutorial: Blog Post API

This tutorial builds a complete Blog module with Post CRUD API.

### Goal

- Create `Modules/Blog`
- Scaffold Post API CRUD
- Migrate and verify module health
- Know where to put business logic

### Step 1: Install (if not done)

```bash
composer require libinkk/modular
php artisan modular:install
# add Modules\\ PSR-4, then:
composer dump-autoload
```

### Step 2: Create the Blog module

```bash
php artisan modular:make Blog
```

You now have:

```
Modules/Blog/
├── Providers/BlogServiceProvider.php
├── Routes/web.php
├── Routes/api.php
├── Config/
├── Database/
├── Views/
├── Lang/
├── Tests/
└── module.json
```

### Step 3: Generate Post CRUD (API mode)

```bash
php artisan modular:crud Blog Post --api
```

Equivalent styles:

```bash
php artisan modular:crud Post --module=Blog --api
php artisan modular:crud Post -m=Blog --api
```

### Step 4: What was created

Inside `Modules/Blog/` you get:

| Artifact | Path example |
| --- | --- |
| Model | `Models/Post.php` |
| Migration | `Database/Migrations/*_create_posts_table.php` |
| Factory / Seeder | `Database/Factories`, `Database/Seeders` |
| Repository + interface | `Repositories/`, `Interfaces/` |
| Service | `Services/PostService.php` |
| Controller | `Controllers/PostController.php` |
| Form requests | `Requests/StorePostRequest.php`, `UpdatePostRequest.php` |
| DTO | `DTO/PostData.php` |
| Actions | `Actions/CreatePost.php`, `UpdatePost.php`, `DeletePost.php` |
| API resource | `Resources/PostResource.php` |
| Policy | `Policies/PostPolicy.php` |
| Routes | `Routes/api.php` (api resource registered) |
| Tests | `Tests/PostCrudTest.php` |

### Step 5: Run migrations

```bash
php artisan migrate
```

Module migrations are discovered automatically. No manual path registration.

### Step 6: Check module status

```bash
php artisan modular:list
php artisan modular:info Blog
php artisan modular:doctor
```

### Step 7: Write business rules

Open:

- `Modules/Blog/Services/PostService.php`
- `Modules/Blog/Actions/CreatePost.php`

Keep the controller thin. Put rules in Service / Action layers.

### Step 8: Hit the API

After your app is running, Post routes from `Modules/Blog/Routes/api.php` are available (prefix depends on the generated route file). Customize validation in Form Requests, then ship.

---

## Full tutorial: Catalog Product feature

Realistic walkthrough for a Catalog module.

### Step A: Create Catalog

```bash
php artisan modular:make Catalog
```

### Step B: Scaffold Product CRUD for API

```bash
php artisan modular:crud Catalog Product --api
```

### Step C: Understand the generated request flow

1. HTTP request hits `ProductController@store`
2. `StoreProductRequest` validates input
3. Data becomes a DTO (`ProductData`)
4. An Action (`CreateProduct`) runs the use case
5. `ProductService` applies business rules
6. `ProductRepository` saves via Eloquent
7. API Resource formats the JSON response

You can change any layer without rewriting the whole feature.

### Step D: Add a nested admin controller

```bash
php artisan modular:controller Catalog API/Admin/ProductController
```

Result:

```
Modules/Catalog/Controllers/API/Admin/ProductController.php
```

Namespaces are generated for you.

### Step E: Add only what you need next

```bash
php artisan modular:service Catalog PricingService
php artisan modular:action Catalog ApplyDiscount
php artisan modular:dto Catalog DiscountData
php artisan modular:job Catalog SyncProductStockJob
php artisan modular:test Catalog ProductPricingTest
```

### Step F: Emergency disable / re-enable

```bash
php artisan modular:disable Catalog
# fix code, deploy, then:
php artisan modular:enable Catalog
php artisan modular:list
```

When disabled, Catalog providers, routes, and related discovery are skipped. Files stay on disk.

---

## Feature walkthroughs

Each feature below is explained step by step.

### 1. Automatic module discovery

**What it does:** On boot, the package scans `Modules/`, reads each `module.json`, skips disabled modules, and registers the rest.

**Steps to see it:**

1. Create two modules:

```bash
php artisan modular:make Orders
php artisan modular:make Payments
```

2. List them:

```bash
php artisan modular:list
```

3. Disable one and list again:

```bash
php artisan modular:disable Payments
php artisan modular:list
```

4. Confirm with doctor:

```bash
php artisan modular:doctor
```

No edits to `bootstrap/providers.php` or `config/app.php` are required for module providers.

### 2. Automatic routes, migrations, views, lang, config

**Steps:**

1. Create a module:

```bash
php artisan modular:make Store
```

2. Add or use generated routes in:

- `Modules/Store/Routes/web.php`
- `Modules/Store/Routes/api.php`

3. Add a migration:

```bash
php artisan modular:migration Store create_items_table
php artisan migrate
```

4. Use views and translations:

```php
return view('store::index');
__('store::messages.module');
```

5. Module config lives under `Modules/Store/Config/` and loads with the module.

### 3. Create a module with options

```bash
php artisan modular:make ApiOnly --api      # api routes only
php artisan modular:make WebOnly --web      # web routes only
php artisan modular:make Bare --empty       # folders + module.json only
php artisan modular:make Mini --minimal     # Controllers, Routes, Provider
```

**Steps:**

1. Pick the option that matches your feature shape.
2. Run `modular:make`.
3. Inspect `Modules/{Name}/` and `module.json`.
4. Generate only the classes you need next.

### 4. Single generators (controller, model, service, ...)

**Example: add a controller + model + request**

```bash
php artisan modular:controller User ProfileController
php artisan modular:model User Profile --migration
php artisan modular:request User UpdateProfileRequest
```

**Command styles (same result):**

```bash
# Style 1: module first
php artisan modular:controller User UserController

# Style 2: --module flag
php artisan modular:controller API/UserController --module=User

# Style 3: short -m flag
php artisan modular:controller API/UserController -m=User
```

**Nested folders:**

```bash
php artisan modular:controller User API/Admin/UserController
# → Modules/User/Controllers/API/Admin/UserController.php
```

### 5. Repository pattern (auto-bound)

**Steps:**

1. Ensure the module exists:

```bash
php artisan modular:make Shop
```

2. Generate a repository:

```bash
php artisan modular:repository Shop ItemRepository
```

3. What you get:

- `Repositories/ItemRepository.php`
- `Interfaces/ItemRepositoryInterface.php`
- Provider binding in `ShopServiceProvider`
- Matching `ItemService` created or smart-merged with DI
- Matching controller wiring when `ItemController` already exists

4. Type-hint the interface in your code:

```php
public function __construct(
    private ItemRepositoryInterface $items
) {}
```

### 6. Auto dependency injection

Naming convention:

- `ProductService` ↔ `ProductController`
- `ProductRepository` ↔ `ProductService`

**Steps:**

1. Create a controller without wiring:

```bash
php artisan modular:controller Shop ProductController --no-inject
```

2. Create a service and wire it:

```bash
php artisan modular:service Shop ProductService --inject
```

3. Open `ProductController` and confirm constructor injection was added.

4. Prompt mode (asks yes/no):

```bash
php artisan modular:service Shop OrderService
```

5. Skip wiring entirely:

```bash
php artisan modular:service Shop QuoteService --no-inject
```

Smart merge adds a `use` import and a promoted constructor property when missing. It does not wipe custom methods.

### 7. Full CRUD generator

**Steps:**

1. Create module:

```bash
php artisan modular:make Inventory
```

2. Generate CRUD:

```bash
php artisan modular:crud Inventory Item --api
```

3. Migrate:

```bash
php artisan migrate
```

4. Customize:

- Validation → Form Requests
- Business rules → Service / Actions
- Persistence → Repository
- Response shape → API Resource

### 8. Enable and disable modules

**Steps:**

1. Disable a feature without deleting code:

```bash
php artisan modular:disable Payment
```

2. Confirm `module.json` has `"enabled": false`.

3. Confirm routes/providers from that module no longer load.

4. Re-enable when ready:

```bash
php artisan modular:enable Payment
```

**When to use:**

- Emergency stop in production
- WIP features kept in repo but off
- Staging-only experimental modules
- Safer maintenance windows

### 9. Rename a module

**Steps:**

1. Rename and update references:

```bash
php artisan modular:rename User Customer
```

2. If the target name already exists:

```bash
php artisan modular:rename User Customer --force
```

3. Verify:

```bash
php artisan modular:list
php artisan modular:info Customer
```

Folder names, class names, namespaces, view namespaces, and related references are updated. Stats are logged in `module.json`.

### 10. Delete a module

```bash
php artisan modular:delete User
php artisan modular:delete User --force
```

Prefer `disable` when you might need the code again.

### 11. Module dependencies

**Steps:**

1. Edit `Modules/Orders/module.json`:

```json
{
  "name": "Orders",
  "description": "Order management",
  "version": "1.0.0",
  "enabled": true,
  "dependencies": ["Users", "Products"]
}
```

2. Ensure dependency modules exist and are available.

3. Run:

```bash
php artisan modular:doctor
```

Missing dependencies are reported so features do not fail silently.

### 12. Module cache (production)

**Steps:**

1. Build cache:

```bash
php artisan modular:cache
```

2. List from cache:

```bash
php artisan modular:list --cached
```

3. Clear when needed:

```bash
php artisan modular:clear
```

4. Keep config tuned for production:

```php
'prefer_cache' => true,
'auto_refresh_cache' => true,
```

`auto_refresh_cache` refreshes after make / enable / disable / rename / delete.

### 13. Info, status, doctor

```bash
php artisan modular:info Blog
php artisan modular:status
php artisan modular:doctor
```

Use these in local setup and CI smoke checks.

### 14. Database helpers (migration, factory, seeder)

```bash
php artisan modular:migration Shop create_coupons_table
php artisan modular:factory Shop CouponFactory
php artisan modular:seeder Shop CouponSeeder
php artisan migrate
```

### 15. Supporting generators

```bash
php artisan modular:event Shop OrderPlaced
php artisan modular:listener Shop SendOrderMail
php artisan modular:job Shop SyncOrderJob
php artisan modular:notification Shop OrderReady
php artisan modular:policy Shop OrderPolicy
php artisan modular:middleware Shop EnsureShopAdmin
php artisan modular:dto Shop OrderData
php artisan modular:action Shop PlaceOrder
php artisan modular:enum Shop OrderStatus
php artisan modular:rule Shop ValidSku
php artisan modular:trait Shop HasSku
php artisan modular:helper Shop OrderHelper
php artisan modular:command Shop SyncOrdersCommand
php artisan modular:resource Shop OrderResource
php artisan modular:test Shop OrderFlowTest
php artisan modular:view Shop admin/dashboard
php artisan modular:lang Shop validation
php artisan modular:config Shop settings
php artisan modular:route Shop
```

Generate only what your feature needs. Stay consistent with the layered flow.

---

## Module structure

```
Modules/
└── User/
    ├── Controllers/
    ├── Requests/
    ├── Models/
    ├── Services/
    ├── Repositories/
    ├── Interfaces/
    ├── Actions/
    ├── DTO/
    ├── Traits/
    ├── Enums/
    ├── Policies/
    ├── Rules/
    ├── Events/
    ├── Listeners/
    ├── Jobs/
    ├── Notifications/
    ├── Resources/
    ├── Helpers/
    ├── Console/
    ├── Database/
    │   ├── Migrations/
    │   ├── Seeders/
    │   └── Factories/
    ├── Config/
    ├── Routes/
    │   ├── api.php
    │   └── web.php
    ├── Views/
    ├── Lang/
    ├── Tests/
    ├── Providers/
    │   └── UserServiceProvider.php
    └── module.json
```

### `module.json`

```json
{
  "name": "User",
  "description": "User Management Module",
  "version": "1.0.0",
  "enabled": true,
  "dependencies": []
}
```

### How assets are used

| Asset | Location | Usage |
| --- | --- | --- |
| Routes | `Modules/*/Routes/{web,api}.php` | Auto-loaded |
| Migrations | `Modules/*/Database/Migrations` | `php artisan migrate` |
| Views | `Modules/User/Views` | `view('user::dashboard')` |
| Lang | `Modules/User/Lang` | `__('user::messages.success')` |
| Provider | `Modules/User/Providers/...` | Auto-registered |
| Config | `Modules/User/Config` | Loaded with the module |

---

## Architecture

Recommended request flow:

```
Controller → Request → DTO → Action → Service → Repository → Model → Database
```

| Layer | Responsibility |
| --- | --- |
| Controller | HTTP only: accept input, call next layer, return response |
| Request | Validation / authorization input |
| DTO | Typed data between layers |
| Action | One use case (`CreateUser`, `UpdateProduct`) |
| Service | Business rules and orchestration |
| Repository | Persistence behind an interface |
| Model | Eloquent entity |

**Thin controller example:**

```php
public function store(StoreUserRequest $request, CreateUser $action)
{
    $user = $action->handle(UserData::fromRequest($request));

    return new UserResource($user);
}
```

Name modules after **business domains** (`Orders`, `Billing`), not framework layers (`Controllers`).

---

## Artisan commands

### Setup and health

| Command | Purpose |
| --- | --- |
| `modular:install` | Create `Modules/`, publish config, cache |
| `modular:list` | List modules |
| `modular:info {name}` | Module details |
| `modular:status` | Package / modules status overview |
| `modular:doctor` | Check PSR-4, providers, routes, cache, dependencies |

### Lifecycle

| Command | Purpose |
| --- | --- |
| `modular:make {name}` | Create module |
| `modular:enable {name}` | Enable module |
| `modular:disable {name}` | Disable module (files kept) |
| `modular:rename {from} {to}` | Rename + rewrite namespaces/refs |
| `modular:delete {name}` | Delete module (confirm / `--force`) |
| `modular:cache` | Build module cache |
| `modular:clear` | Clear module cache |

### Generators

| Command | Example |
| --- | --- |
| CRUD | `php artisan modular:crud User Product` |
| CRUD API | `php artisan modular:crud User Product --api` |
| Controller | `php artisan modular:controller User UserController` |
| Model | `php artisan modular:model User User` |
| Model + migration | `php artisan modular:model User User --migration` |
| Request | `php artisan modular:request User StoreUserRequest` |
| Service | `php artisan modular:service User UserService` |
| Repository | `php artisan modular:repository User UserRepository` |
| Resource | `php artisan modular:resource User UserResource` |
| Event | `php artisan modular:event User UserCreated` |
| Listener | `php artisan modular:listener User SendWelcomeEmail` |
| Job | `php artisan modular:job User SyncUserJob` |
| Notification | `php artisan modular:notification User WelcomeNotification` |
| Policy | `php artisan modular:policy User UserPolicy` |
| Middleware | `php artisan modular:middleware User AdminMiddleware` |
| DTO | `php artisan modular:dto User UserData` |
| Action | `php artisan modular:action User CreateUser` |
| Enum | `php artisan modular:enum User UserStatus` |
| Rule | `php artisan modular:rule User PhoneNumberRule` |
| Test | `php artisan modular:test User UserTest` |
| Migration | `php artisan modular:migration User create_users_table` |
| Factory | `php artisan modular:factory User UserFactory` |
| Seeder | `php artisan modular:seeder User UserSeeder` |
| Route | `php artisan modular:route User` |
| Config | `php artisan modular:config User` |
| Lang | `php artisan modular:lang User` |
| View | `php artisan modular:view User dashboard` |
| Trait | `php artisan modular:trait User HasProfile` |
| Helper | `php artisan modular:helper User` |
| Console | `php artisan modular:console User SyncUsersCommand` |

---

## Configuration

Publish (also done by `modular:install`):

```bash
php artisan vendor:publish --tag=modular-config
```

`config/modular.php`:

```php
return [
    'modules_path' => base_path('Modules'),
    'cache_file' => base_path('bootstrap/cache/modular_modules.php'),

    // Use cache on boot when the file exists
    'prefer_cache' => true,

    // Refresh cache after make / enable / disable / rename / delete
    'auto_refresh_cache' => true,
];
```

**Production tip:** keep `prefer_cache` on and run `php artisan modular:cache` in deploy scripts (or rely on `auto_refresh_cache` after lifecycle commands).

---

## Benchmarks

Local micro-benchmarks on **PHP 8.5.4** (Linux), measuring `ModuleRepository::all()` discovery for scaffolded modules (average of many iterations). Numbers are indicative. Your disk / OS will vary.

### Module discovery: scan vs cache

| Modules | Scan (avg) | Cache (avg) | Speedup |
| ---: | ---: | ---: | ---: |
| 10 | ~0.41 ms | ~0.06 ms | **~6.4×** |
| 25 | ~0.87 ms | ~0.14 ms | **~6.1×** |
| 50 | ~1.71 ms | ~0.25 ms | **~6.7×** |
| 100 | ~3.63 ms | ~0.53 ms | **~6.9×** |

**Takeaway:** With many modules, enabling cache (`modular:cache` + `prefer_cache`) keeps boot discovery roughly **6 to 7× faster** than scanning every `module.json`.

### Scaffolding throughput

| Operation | Result |
| --- | --- |
| Scaffold **1** module (full template) | ~**0.6 ms** |
| Scaffold **50** modules | ~**25 ms** total |

### Test suite

| Metric | Result |
| --- | --- |
| PHPUnit | **43** tests / **425** assertions |
| Runtime (local) | ~**1.1 s** |

```bash
composer install
vendor/bin/phpunit
```

---

## Testing

```bash
composer install
vendor/bin/phpunit
# or
composer test
```

Coverage areas:

- Module create / CRUD / generators
- Enable / disable / rename / delete / cache / doctor
- Auto-registration of enabled vs disabled modules
- Constructor DI wiring / smart merge
- High-level end-to-end workflows + `ModuleRepository` unit tests

---

## CI

GitHub Actions workflow: [`.github/workflows/tests.yml`](.github/workflows/tests.yml)

- Triggers: push, pull request, manual dispatch
- Matrix: **PHP 8.1 / 8.2 / 8.3 / 8.4 / 8.5 / 8.6**
- Uses [`shivammathur/setup-php`](https://github.com/shivammathur/setup-php)
- Runs `composer update` + `vendor/bin/phpunit`

---

## Best practices

1. **One business domain per module** (`Orders`, not `HttpStuff`).
2. **Keep controllers thin**. Push rules into Actions / Services.
3. **Type-hint repository interfaces**, not concrete classes.
4. **Declare dependencies** in `module.json` for cross-module needs.
5. **Disable instead of deleting** when a feature is temporarily unsafe.
6. **Cache modules in production**. Refresh after deploy / lifecycle changes.
7. **Run `modular:doctor`** in CI or before release.
8. **Pick one command syntax style** and stick to it in the team.
9. **Migrate domains gradually** from a legacy `app/` into `Modules/`.
10. **Commit generated structure**. Customize business logic in Services/Actions.

---

## Troubleshooting

| Problem | Fix |
| --- | --- |
| Class not found under `Modules\` | Add PSR-4 `"Modules\\": "Modules/"` and `composer dump-autoload` |
| Module routes / provider missing | Check `module.json` → `"enabled": true`, then `modular:list` / `modular:doctor` |
| Disabled module still loads | Clear opcache / restart workers; rebuild cache: `modular:clear && modular:cache` |
| Doctor fails on dependencies | Create or enable listed dependency modules |
| Rename conflict | Use `--force` only if overwriting the target is intentional |
| Cache stale after manual file edits | `php artisan modular:clear` then `modular:cache` |
| Generators fail "module not found" | Create the module first with `modular:make` |

---

## FAQ

**Does install edit my `composer.json`?**  
No. You add the `Modules\\` PSR-4 entry yourself.

**Do I register providers in `bootstrap/providers.php`?**  
No for module providers. Discovery handles enabled modules.

**Can I use only some layers (no repository)?**  
Yes. Generators are optional. Use what fits your team.

**Is this only for APIs?**  
No. Web routes, views, and lang are first-class. Use `--api` when you want API-oriented CRUD stubs.

**PHP 8.6 supported?**  
Yes. Constraint is `^8.1`, and CI includes **8.6** (stable as of [19 Nov 2026](https://www.php.net/releases/8_6_0.php)).

---

## Roadmap

- Deeper architecture analyzer / dependency graph
- OpenAPI generation from module routes
- Multi-tenant module scoping helpers
- AI-assisted scaffold refinements

Ideas welcome via issues / contact on [libinkk.in](https://www.libinkk.in).

---

## License

MIT © **Libin K K**  
Email: [libinkk1999@gmail.com](mailto:libinkk1999@gmail.com)  
Website: [www.libinkk.in](https://www.libinkk.in)

---

**Install once. Generate modules. Focus on business logic.**

For extra deep-dive notes, see [DOCUMENTATION.md](DOCUMENTATION.md).
