# libinkk/modular

**Enterprise-grade Laravel modular architecture with zero manual configuration.**


|                     |                                                  |
| ------------------- | ------------------------------------------------ |
| **Package**         | `libinkk/modular`                                |
| **Current version** | `v1.2.0`                                         |
| **License**         | MIT                                              |
| **PHP**             | `^8.1` (Laravel 13 needs `^8.3`)                 |
| **Laravel**         | `10` / `11` / `12` / `13`                        |
| **Website**         | [https://www.libinkk.in](https://www.libinkk.in) |
| **Author**          | Libin K K                                        |


---

## Table of contents

1. [What is this package?](#1-what-is-this-package)
2. [The problem Laravel projects face](#2-the-problem-laravel-projects-face)
3. [How this package solves it](#3-how-this-package-solves-it)
4. [Who should use it?](#4-who-should-use-it)
5. [Core concepts](#5-core-concepts)
6. [Features overview](#6-features-overview)
7. [Installation](#7-installation)
8. [Quick start tutorial](#8-quick-start-tutorial)
9. [Full tutorial: build a Product API](#9-full-tutorial-build-a-product-api)
10. [Module structure](#10-module-structure)
11. [Architecture explained](#11-architecture-explained)
12. [All Artisan commands](#12-all-artisan-commands)
13. [Enable and disable modules](#13-enable-and-disable-modules)
14. [Module dependencies](#14-module-dependencies)
15. [Module cache](#15-module-cache)
16. [Use cases](#16-use-cases)
17. [Before vs after](#17-before-vs-after)
18. [Configuration](#18-configuration)
19. [Best practices](#19-best-practices)
20. [Troubleshooting](#20-troubleshooting)
21. [Roadmap](#21-roadmap)
22. [FAQ](#22-faq)

---



## 1. What is this package?

`libinkk/modular` helps you build large Laravel applications by organizing code into **business modules** instead of dumping everything into `app/`.

Each feature (Users, Orders, Products, Billing) becomes its own folder under `Modules/`. That folder holds controllers, models, services, routes, migrations, views, tests, and more.

The package then:

- Discovers modules automatically
- Registers service providers automatically
- Loads routes, migrations, views, translations, and config automatically
- Generates clean layered code with Artisan commands
- Lets you enable, disable, rename, or delete modules safely

**Goal:** Install once. Create modules. Focus on business logic.

---



## 2. The problem Laravel projects face

As a Laravel app grows, these issues appear again and again.

### Problem 1: Flat and messy folders

Everything lives under `app/`:

```
app/
├── Http/Controllers/
├── Models/
├── Services/
└── ...
```

When you have Users, Orders, Inventory, Billing, and Reports, finding related files becomes hard. Controllers for Orders sit next to Users. Models mix together. New developers get lost.

### Problem 2: Manual registration everywhere

For each feature you often need to:

1. Create folders by hand
2. Create a service provider
3. Register the provider
4. Load routes
5. Load migrations
6. Load views
7. Load config
8. Bind repository interfaces

One missed step and something breaks silently.

### Problem 3: Fat controllers

Business rules, validation, database queries, and response formatting often end up in one controller method. That makes testing hard and reuse almost impossible.

### Problem 4: No clear feature boundary

You cannot turn off one feature without deleting files. You cannot see which features depend on which other features. Shipping a half-ready feature into production is risky.

### Problem 5: Slow onboarding

Every project invents a slightly different structure. New teammates spend days learning "how we organize things here" instead of shipping features.

---



## 3. How this package solves it


| Current problem                   | How libinkk/modular solves it                                         |
| --------------------------------- | --------------------------------------------------------------------- |
| Messy `app/` folders              | Each feature is a self-contained module under `Modules/`              |
| Manual provider registration      | Providers are discovered and registered automatically                 |
| Manual route loading              | `Routes/web.php` and `Routes/api.php` load automatically              |
| Manual migration loading          | Module migrations run with `php artisan migrate`                      |
| Manual view / lang / config setup | Namespaced views, translations, and config load automatically         |
| Fat controllers                   | Generators encourage Request → DTO → Action → Service → Repository    |
| No feature on/off switch          | `modular:enable` and `modular:disable`                                |
| Unclear dependencies              | `module.json` declares dependencies and fails boot if missing         |
| Slow scaffolding                  | `modular:make` and `modular:crud` generate production-ready structure |
| Production scan cost              | Module cache avoids scanning every request                            |




### Simple example

**Without this package:**

```
Create folder → Create provider → Register provider → Load routes →
Load migrations → Bind repository → Write controller → Start coding
```

**With this package:**

```bash
composer require libinkk/modular
php artisan modular:install
php artisan modular:make Catalog
php artisan modular:crud Catalog Product --api
```

You get a full Product CRUD scaffold inside `Modules/Catalog/` and can start writing business rules immediately.

---



## 4. Who should use it?

This package is a strong fit if you:

- Build medium or large Laravel apps
- Work in a team that needs a shared structure
- Want clean API modules (Users, Orders, Payments)
- Prefer repository + service + DTO patterns
- Need to enable or disable features without deleting code
- Want generators instead of copy-paste scaffolding

It is less necessary if you only have a tiny single-feature app. Even then, learning modular structure early still helps.

---



## 5. Core concepts



### Business module

A module is one business area. Example:

- `Modules/User`
- `Modules/Order`
- `Modules/Payment`

Not "Controllers module" or "Models module". Modules are named after the business, not the framework layer.

### Zero manual registration

You should not edit `bootstrap/providers.php` or `config/app.php` for module features. Discovery handles it.

### `module.json`

Every module has a manifest:

```json
{
    "name": "User",
    "description": "User management module",
    "version": "1.0.0",
    "enabled": true,
    "dependencies": []
}
```

This file controls:

- Name
- Description
- Version
- Enabled / disabled state
- Dependencies on other modules



### Recommended request flow

```
Controller → Request → DTO → Action → Service → Repository → Model → Database
```


| Layer      | Job                                                         |
| ---------- | ----------------------------------------------------------- |
| Controller | HTTP only: accept request, call next layer, return response |
| Request    | Validation and authorization input                          |
| DTO        | Typed data object passed between layers                     |
| Action     | One clear use case (CreateUser, UpdateProduct)              |
| Service    | Business rules and orchestration                            |
| Repository | Database access behind an interface                         |
| Model      | Eloquent entity                                             |


---



## 6. Features overview

- Modular architecture under `Modules/`
- Automatic module discovery
- Automatic service provider registration
- Automatic route discovery (`web.php`, `api.php`)
- Automatic migration discovery
- Automatic view discovery (`user::dashboard`)
- Automatic translation discovery (`user::messages.success`)
- Automatic configuration loading
- Repository pattern with interface binding
- Auto DI wiring for service ↔ controller and repository ↔ service (prompt by default; `--inject` / `--no-inject` skip the prompt)
- Service layer support
- DTO support
- Action classes
- Events, listeners, jobs, policies, notifications
- Testing support and generators
- Full CRUD generator (including `--api` mode)
- Module enable / disable
- Module list, info, status, rename, delete
- Module dependency checks
- Module cache for production performance
- Health checks with `modular:doctor`

---



## 7. Installation



### Step 1: Require the package

```bash
composer require libinkk/modular
```



### Step 2: Install

```bash
php artisan modular:install
```

This command:

1. Creates the `Modules/` folder
2. Publishes `config/modular.php`
3. Generates the module cache
4. Reminds you about Composer PSR-4 (it does **not** edit `composer.json`)



### Step 3: Add PSR-4 autoload in your app

Open your application `composer.json` and add:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Modules\\": "Modules/"
    }
}
```

Then run:

```bash
composer dump-autoload
```



### Step 4: Create your first module

```bash
php artisan modular:make User
```

Done. Your first module is ready.

---



## 8. Quick start tutorial



### Goal

Create a `Blog` module with a `Post` CRUD API in a few minutes.

### 1. Install and create the module

```bash
composer require libinkk/modular
php artisan modular:install
# add Modules\\ PSR-4, then:
composer dump-autoload
php artisan modular:make Blog
```



### 2. Generate CRUD

```bash
php artisan modular:crud Blog Post --api
```

This creates (inside `Modules/Blog/`):

- Model
- Migration
- Factory
- Seeder
- Repository + interface + binding
- DTO
- Actions
- Service
- Controller
- Form requests
- API resource
- Policy
- Routes
- Feature tests



### 3. Run migrations

```bash
php artisan migrate
```

Module migrations are discovered automatically. No extra setup.

### 4. Check module health

```bash
php artisan modular:list
php artisan modular:info Blog
php artisan modular:doctor
```



### 5. Start coding business rules

Open the generated Service / Action files and add your logic. Keep controllers thin.

---



## 9. Full tutorial: build a Product API

This tutorial walks through a realistic Catalog module.

### Step A: Create the Catalog module

```bash
php artisan modular:make Catalog
```

You now have:

```
Modules/Catalog/
├── Providers/CatalogServiceProvider.php
├── Routes/web.php
├── Routes/api.php
├── Config/
├── Database/
├── Views/
├── Lang/
├── Tests/
└── module.json
```



### Step B: Scaffold Product CRUD for API

```bash
php artisan modular:crud Catalog Product --api
```

Example style alternatives (all valid):

```bash
# Module first
php artisan modular:crud Catalog Product --api

# Resource first with flag
php artisan modular:crud Product --module=Catalog --api

# Short flag
php artisan modular:crud Product -m=Catalog --api
```



### Step C: Understand the generated flow

Example create flow:

1. HTTP request hits `ProductController@store`
2. `StoreProductRequest` validates input
3. Data becomes a DTO (for example `ProductData`)
4. An Action (for example `CreateProduct`) runs the use case
5. `ProductService` applies business rules
6. `ProductRepository` saves via Eloquent
7. API Resource formats the JSON response

You can change any layer without rewriting the whole feature.

### Step D: Add a nested controller later

```bash
php artisan modular:controller Catalog API/Admin/ProductController
```

Result:

```
Modules/Catalog/Controllers/API/Admin/ProductController.php
```

Namespaces are generated for you.

### Step E: Add only what you need

```bash
php artisan modular:service Catalog PricingService
php artisan modular:action Catalog ApplyDiscount
php artisan modular:dto Catalog DiscountData
php artisan modular:job Catalog SyncProductStockJob
php artisan modular:test Catalog ProductPricingTest
```



### Step F: Disable during maintenance

If Product pricing is broken in production and you need a quick stop:

```bash
php artisan modular:disable Catalog
```

The Catalog module stops loading (providers, routes, and related discovery). Files stay on disk.

When fixed:

```bash
php artisan modular:enable Catalog
```

---



## 10. Module structure

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



### How assets are used


| Asset      | Location                                         | Usage                          |
| ---------- | ------------------------------------------------ | ------------------------------ |
| Routes     | `Modules/*/Routes/{web,api}.php`                 | Auto-loaded                    |
| Migrations | `Modules/*/Database/Migrations`                  | `php artisan migrate`          |
| Views      | `Modules/User/Views`                             | `view('user::dashboard')`      |
| Lang       | `Modules/User/Lang`                              | `__('user::messages.success')` |
| Provider   | `Modules/User/Providers/UserServiceProvider.php` | Auto-registered                |
| Config     | `Modules/User/Config`                            | Loaded with the module         |


---



## 11. Architecture explained



### Why layers?

Because each layer has one job.

**Bad (everything in controller):**

```php
public function store(Request $request)
{
    $data = $request->validate([...]);

    // business rules mixed here
    if (User::where('email', $data['email'])->exists()) {
        return response()->json(['error' => 'taken'], 422);
    }

    $user = User::create($data);
    Mail::to($user)->send(new WelcomeMail($user));

    return response()->json($user, 201);
}
```

**Better (with modular layers):**

```php
// Controller: HTTP only
public function store(StoreUserRequest $request, CreateUser $action)
{
    $user = $action->handle(UserData::fromRequest($request));

    return new UserResource($user);
}
```

```php
// Action: one use case
public function handle(UserData $data): User
{
    return $this->users->create($data);
}
```

```php
// Service / Repository: rules and persistence stay outside HTTP
```

This makes unit testing and reuse much easier.

### Repository pattern (auto-bound)

```bash
php artisan modular:repository User UserRepository
```

This generates:

- Repository class
- Interface
- Service provider binding
- Matching `UserService` (created with repository DI if missing, or smart-merged if it already exists)
- Matching `UserController` constructor wiring when that controller already exists

### Auto dependency injection

By default, generators ask whether to wire related classes by naming convention (`ProductService` ↔ `ProductController`, `ProductRepository` ↔ `ProductService`):

```bash
php artisan modular:service User ProductService
# asks: Wire matching dependencies into related classes? (yes/no)

php artisan modular:repository User ProductRepository --inject
php artisan modular:controller User ProductController --no-inject
```

`--inject` and `--no-inject` skip the prompt. Smart merge adds a `use` import and a promoted constructor property when missing. It does not wipe methods or replace an existing constructor body.

So you can type-hint the interface:

```php
public function __construct(
    private UserRepositoryInterface $users
) {}
```

You do not write the bind call by hand for generated repositories.

---



## 12. All Artisan commands



### Setup and health


| Command                         | Purpose                                                |
| ------------------------------- | ------------------------------------------------------ |
| `php artisan modular:install`   | Create Modules folder, publish config, cache           |
| `php artisan modular:list`      | List all modules                                       |
| `php artisan modular:info User` | Show details for one module                            |
| `php artisan modular:status`    | Show module status overview                            |
| `php artisan modular:doctor`    | Diagnose PSR-4, providers, routes, cache, dependencies |




### Module lifecycle


| Command                                    | Purpose                             |
| ------------------------------------------ | ----------------------------------- |
| `php artisan modular:make User`            | Create a new module                 |
| `php artisan modular:enable User`          | Enable a module                     |
| `php artisan modular:disable User`         | Disable a module                    |
| `php artisan modular:rename User Customer` | Rename module and update references |
| `php artisan modular:delete User`          | Delete a module (with confirmation) |
| `php artisan modular:cache`                | Build module cache                  |
| `php artisan modular:clear`                | Clear module cache                  |




### Generators


| Command       | Example                                                     |
| ------------- | ----------------------------------------------------------- |
| CRUD          | `php artisan modular:crud User Product`                     |
| CRUD API mode | `php artisan modular:crud User Product --api`               |
| Controller    | `php artisan modular:controller User UserController`        |
| Model         | `php artisan modular:model User User`                       |
| Model + mig.  | `php artisan modular:model User User --migration`           |
| Request       | `php artisan modular:request User StoreUserRequest`         |
| Service       | `php artisan modular:service User UserService`              |
| Repository    | `php artisan modular:repository User UserRepository`        |
| Skip DI wire  | `--no-inject` (no prompt); `--inject` wires without asking |
| Resource      | `php artisan modular:resource User UserResource`            |
| Event         | `php artisan modular:event User UserCreated`                |
| Listener      | `php artisan modular:listener User SendWelcomeEmail`        |
| Job           | `php artisan modular:job User SyncUserJob`                  |
| Notification  | `php artisan modular:notification User WelcomeNotification` |
| Policy        | `php artisan modular:policy User UserPolicy`                |
| Middleware    | `php artisan modular:middleware User AdminMiddleware`       |
| DTO           | `php artisan modular:dto User UserData`                     |
| Action        | `php artisan modular:action User CreateUser`                |
| Enum          | `php artisan modular:enum User UserStatus`                  |
| Rule          | `php artisan modular:rule User PhoneNumberRule`             |
| Test          | `php artisan modular:test User UserTest`                    |
| Migration     | `php artisan modular:migration User create_users_table`     |
| Factory       | `php artisan modular:factory User UserFactory`              |
| Seeder        | `php artisan modular:seeder User UserSeeder`                |
| Route         | `php artisan modular:route User`                            |
| Config        | `php artisan modular:config User`                           |
| Lang          | `php artisan modular:lang User`                             |
| View          | `php artisan modular:view User dashboard`                   |
| Trait         | `php artisan modular:trait User HasProfile`                 |
| Helper        | `php artisan modular:helper User`                           |
| Console       | `php artisan modular:console User SyncUsersCommand`         |




### Command syntax styles

All three styles work. Pick one style and stay consistent in your team.

```bash
# Style 1: module first
php artisan modular:controller User UserController

# Style 2: --module flag
php artisan modular:controller API/UserController --module=User

# Style 3: short -m flag
php artisan modular:controller API/UserController -m=User
```

Nested paths are supported:

```bash
php artisan modular:controller User API/Admin/UserController
# → Modules/User/Controllers/API/Admin/UserController.php
```

---



## 13. Enable and disable modules



### What it does

- `disable` turns a module **off** at boot time
- `enable` turns it **back on**
- Files are **not** deleted

```bash
php artisan modular:disable Payment
php artisan modular:enable Payment
```

This updates `module.json`:

```json
{
    "name": "Payment",
    "enabled": false
}
```

When disabled, the package ignores that module during discovery. Providers, routes, and related module assets from that module are not loaded.

### When to use it

1. **Emergency stop** - A feature is broken. Disable it quickly without removing code.
2. **Incomplete features** - Keep WIP modules in the repo but disabled in production.
3. **Environment control** - Enable experimental modules only on staging.
4. **Safer maintenance** - Disable, fix, re-enable.



### Example

```bash
# Payment gateway bug in production
php artisan modular:disable Payment

# Fix code, deploy, then:
php artisan modular:enable Payment
php artisan modular:list
```

---



## 14. Module dependencies

Declare dependencies in `module.json`:

```json
{
    "name": "Orders",
    "description": "Order management",
    "version": "1.0.0",
    "enabled": true,
    "dependencies": [
        "Users",
        "Products"
    ]
}
```

If `Users` or `Products` is missing or not available as required, the package reports an error before the app continues with a broken feature graph.

This prevents silent failures like Orders booting while Product lookups do not exist.

---



## 15. Module cache

Scanning every `module.json` on each request is fine in local development, but production benefits from cache.

```bash
php artisan modular:cache
php artisan modular:clear
```

Config options in `config/modular.php`:

```php
return [
    'modules_path' => base_path('Modules'),
    'cache_file' => base_path('bootstrap/cache/modular_modules.php'),
    'prefer_cache' => true,
    'auto_refresh_cache' => true,
];
```

- `prefer_cache` - use cache file on boot when it exists
- `auto_refresh_cache` - refresh cache after make / enable / disable / rename / delete

---



## 16. Use cases



### Use case 1: Multi-team product

Team A owns `Modules/User`.  
Team B owns `Modules/Billing`.  
Team C owns `Modules/Reporting`.

Each team works inside its own module with fewer merge conflicts and clearer ownership.

### Use case 2: SaaS with optional features

```bash
php artisan modular:disable Invoicing   # plan without invoices
php artisan modular:enable Invoicing    # plan with invoices
```

Feature flags at module level become simple and explicit.

### Use case 3: Rapid API development

```bash
php artisan modular:make Inventory
php artisan modular:crud Inventory Item --api
```

You get a consistent API structure across all resources.

### Use case 4: Migrating a legacy Laravel app

Move one domain at a time:

1. Create `Modules/Orders`
2. Move Orders code into the module
3. Keep old code until cutover
4. Disable or delete old paths when ready



### Use case 5: Teaching clean architecture

Generators show juniors the expected path:

```
Controller → Request → DTO → Action → Service → Repository
```

The structure itself becomes documentation.

---



## 17. Before vs after



### Before (traditional Laravel growth)

```
app/Http/Controllers/UserController.php
app/Http/Controllers/OrderController.php
app/Http/Controllers/PaymentController.php
app/Models/User.php
app/Models/Order.php
app/Models/Payment.php
app/Services/ somehow mixed
manual provider edits
manual route includes
unclear ownership
```



### After (with libinkk/modular)

```
Modules/User/...
Modules/Order/...
Modules/Payment/...
```

Each module owns its HTTP layer, domain logic, database files, views, lang, and tests.

### Developer workflow comparison


| Traditional                      | With libinkk/modular |
| -------------------------------- | -------------------- |
| Create folders manually          | `modular:make`       |
| Register provider                | Automatic            |
| Wire routes / migrations / views | Automatic            |
| Hand-write CRUD boilerplate      | `modular:crud`       |
| Bind repositories manually       | Generated binding    |
| Delete code to turn feature off  | `modular:disable`    |


---



## 18. Configuration

Published file: `config/modular.php`

```php
return [
    'modules_path' => base_path('Modules'),
    'cache_file' => base_path('bootstrap/cache/modular_modules.php'),
    'prefer_cache' => true,
    'auto_refresh_cache' => true,
];
```


| Key                  | Meaning                                |
| -------------------- | -------------------------------------- |
| `modules_path`       | Where modules live                     |
| `cache_file`         | Cached module list path                |
| `prefer_cache`       | Boot from cache when available         |
| `auto_refresh_cache` | Refresh cache after lifecycle commands |


---



## 19. Best practices

1. Put business features in `Modules/`, not in `app/`.
2. Keep controllers thin. Put rules in Action / Service / Repository.
3. Prefer generators (`modular:*`) over hand-made folders.
4. Do not manually register module providers, routes, migrations, views, or lang.
5. Keep `module.json` accurate (`enabled`, `dependencies`, version).
6. Use `modular:doctor` when something looks wrong.
7. Use `modular:crud --api` for API resources to stay consistent.
8. Disable unfinished modules in production instead of shipping half-wired features.
9. Run `modular:cache` as part of your production deploy if you manage cache explicitly.
10. Pick one command syntax style for the whole team.



### Checklist when adding a feature

- [x] Create or reuse a module (`modular:make` or existing `Modules/*`)
- [x] Prefer `modular:crud` for full resource scaffolding
- [ ] Generate remaining classes with `modular:*`
- [ ] Keep logic out of controllers
- [ ] Declare dependencies in `module.json` when needed
- [ ] Add tests (`modular:test` or CRUD-generated tests)
- [ ] Run `modular:doctor`

---



## 20. Troubleshooting



### Classes not found under `Modules\`

Cause: missing Composer PSR-4 mapping.

Fix:

```json
"Modules\\": "Modules/"
```

Then:

```bash
composer dump-autoload
php artisan modular:doctor
```



### Module exists but routes do not load

Checks:

1. Is the module enabled? (`php artisan modular:list`)
2. Do `Routes/web.php` or `Routes/api.php` exist?
3. Clear and rebuild cache:

```bash
php artisan modular:clear
php artisan modular:cache
```



### Disabled module still seems active

Confirm `module.json` has `"enabled": false`, then rebuild cache and restart PHP workers (Octane / queue / RoadRunner if used).

### Dependency boot error

Read the missing dependency name, enable or create that module, then boot again.

```bash
php artisan modular:make Users
php artisan modular:enable Users
```



### Need a health report

```bash
php artisan modular:doctor
```

This checks modules path, PSR-4, providers, routes, cache, and dependencies.

---



## 21. Roadmap



### Current (v1.2.0)

- Module generator
- Automatic discovery
- Broad code generators
- CRUD generator (including API mode)
- Repository automation
- Service / DTO / Action workflow
- Module cache
- Enable / disable / rename / delete
- Dependency management
- Doctor / info / status DX commands



### Next ideas

- Architecture analyzer
- Project health checker expansions
- AI-assisted scaffold generator
- OpenAPI documentation generator
- Module dependency graph visualization
- Multi-tenant module support

---



## 22. FAQ



### Does this replace Laravel?

No. It sits on top of Laravel and organizes your application into modules.

### Do I still use `app/`?

Yes for app-level wiring. Business features should live in `Modules/`.

### Will disable delete my code?

No. Disable only stops the module from loading at boot.

### Can I use nested folders?

Yes.

```bash
php artisan modular:controller User API/Admin/UserController
```



### Does CRUD create tests?

Yes. CRUD scaffolding includes feature tests along with the usual layers.

### Is Laravel 12 and 13 supported?

Yes. The package supports Laravel `10`, `11`, `12`, and `13`.

- Laravel `10` / `11` / `12`: PHP `^8.1` (follow each Laravel version’s own PHP range)
- Laravel `13`: PHP `^8.3` (Laravel 13 requires PHP 8.3 or higher)

### Where do I get help?

- Website: [https://www.libinkk.in](https://www.libinkk.in)
- Package: `libinkk/modular`
- Author: Libin K K

---



## Quick reference card

```bash
# Install
composer require libinkk/modular
php artisan modular:install
composer dump-autoload

# Create
php artisan modular:make User
php artisan modular:crud User Product --api

# Manage
php artisan modular:list
php artisan modular:info User
php artisan modular:disable User
php artisan modular:enable User
php artisan modular:doctor

# Cache
php artisan modular:cache
php artisan modular:clear
```

---



## Philosophy

A Laravel application should be organized by **business modules**, not only by framework folders.

Every module should be:

- Independent
- Testable
- Maintainable
- Scalable
- Reusable

**Install once. Generate modules. Focus only on business logic.**

Everything else is handled by `libinkk/modular`.

---

*Documentation for* `libinkk/modular` *v1.2.0 - ready for website publish.*