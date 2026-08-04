# libinkk/modular

> The easiest way to build scalable Laravel applications using Modular Architecture.

`libinkk/modular` is an opinionated Laravel package that helps developers build large, maintainable, and scalable applications using a modular architecture with **zero manual configuration**.

Instead of manually creating folders, registering service providers, loading routes, migrations, views, and configurations, `libinkk/modular` automates the entire workflow.

---

## Vision

Build enterprise-level Laravel applications without worrying about project structure.

Our goal is to provide:

- Zero configuration
- Opinionated architecture
- Automatic discovery
- Production-ready code generation
- Excellent developer experience

---

## Why libinkk/modular?

As Laravel projects grow, maintaining a clean architecture becomes difficult.

Common problems include:

- Different folder structures across projects
- Manual Service Provider registration
- Manual route loading
- Manual migration loading
- Poor code organization
- Large controllers
- Mixed business logic
- Difficult project maintenance
- Slow onboarding for new developers

`libinkk/modular` solves these problems by enforcing a clean modular architecture.

---

## Features

- Modular Architecture
- Automatic Module Discovery
- Automatic Service Provider Registration
- Automatic Route Discovery
- Automatic Migration Discovery
- Automatic View Discovery
- Automatic Translation Discovery
- Automatic Configuration Loading
- Repository Pattern Support
- Service Layer Support
- DTO Support
- Action Classes
- Event & Listener Support
- Job Support
- Policy Support
- Notification Support
- Testing Support
- CRUD Generator
- Module Cache
- Module Enable/Disable
- Module Dependency Management

---

## Installation

```bash
composer require libinkk/modular
```

After installation, the package automatically:

- Creates the `Modules` directory
- Registers the package
- Publishes configuration
- Starts discovering modules

No manual configuration required.

---

## Folder Structure

```
app/
bootstrap/
config/
Modules/
routes/
storage/
vendor/
```

---

## Module Structure

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

Each module is completely isolated.

---

## Creating a Module

```bash
php artisan modular:make User
```

This command generates `Modules/User/` including:

- Provider
- Routes
- Config
- Database
- Views
- Language
- Tests
- `module.json`

---

## Module Configuration

Every module contains a `module.json`.

```json
{
    "name": "User",
    "description": "User Management Module",
    "version": "1.0.0",
    "enabled": true,
    "dependencies": []
}
```

This file manages:

- Module name
- Version
- Description
- Enable/Disable status
- Dependencies

---

## Automatic Discovery

### Module Discovery

During application boot, the package automatically:

1. Scans the `Modules` directory
2. Finds every `module.json`
3. Ignores disabled modules
4. Loads Service Providers
5. Registers everything automatically

No manual registration is required.

### Service Provider Registration

Each module contains `Providers/UserServiceProvider.php`. The package registers every provider automatically.

Developers never need to edit:

- `bootstrap/providers.php`
- `config/app.php`

### Route Discovery

The package automatically loads:

- `Modules/*/Routes/web.php`
- `Modules/*/Routes/api.php`

No `RouteServiceProvider` modifications are required.

### Migration Discovery

Every migration inside `Modules/*/Database/Migrations` runs automatically:

```bash
php artisan migrate
```

### View Discovery

Views inside `Modules/User/Views` can be used like:

```php
return view('user::dashboard');
```

### Translation Discovery

Language files inside `Modules/User/Lang` can be accessed using:

```php
__('user::messages.success');
```

---

## Command Syntax

The package supports multiple command styles. All three generate the same result:

```bash
# Style 1 — module first
php artisan modular:controller User UserController

# Style 2 — --module flag
php artisan modular:controller API/UserController --module=User

# Style 3 — short flag
php artisan modular:controller API/UserController -m=User
```

### Nested Folder Support

```bash
php artisan modular:controller User API/Admin/UserController
```

Output:

```
Modules/User/Controllers/API/Admin/UserController.php
```

Namespaces are generated automatically.

---

## Available Commands

| Command | Example |
| --- | --- |
| Module | `php artisan modular:make User` |
| Controller | `php artisan modular:controller User UserController` |
| Model | `php artisan modular:model User User` |
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

### Repository Generator

```bash
php artisan modular:repository User UserRepository
```

Automatically generates:

- Repository
- Interface
- Service Provider Binding

---

## CRUD Generator

Generate an entire CRUD module with one command:

```bash
php artisan modular:crud User Product
```

Automatically creates:

- Model
- Migration
- Factory
- Seeder
- Repository
- Repository Interface
- DTO
- Actions
- Service
- Controller
- Form Requests
- API Resource
- Policy
- Routes
- Feature Tests

---

## Repository Pattern

The package automatically binds interfaces. Instead of manually writing:

```php
$this->app->bind(
    UserRepositoryInterface::class,
    UserRepository::class
);
```

everything is generated automatically.

---

## Module Management

### Enable / Disable

```bash
php artisan modular:disable User
php artisan modular:enable User
```

No code deletion is required.

### List Modules

```bash
php artisan modular:list
```

### Rename Module

```bash
php artisan modular:rename User Customer
```

Automatically updates:

- Folder names
- Namespaces
- Providers
- Configuration
- References

### Delete Module

```bash
php artisan modular:delete User
```

Safely removes the module after confirmation.

### Module Cache

```bash
php artisan modular:cache   # Generate cache
php artisan modular:clear   # Clear cache
```

Caching avoids scanning every module on each request and improves production performance.

---

## Module Dependencies

```json
{
    "name": "Orders",
    "dependencies": [
        "Users",
        "Products"
    ]
}
```

If a dependency is missing, the package reports an error before booting the application.

---

## Recommended Architecture

```
Controller → Request → DTO → Action → Service → Repository → Model → Database
```

Business logic should never exist inside controllers.

---

## Developer Workflow

### Traditional Laravel

```
Create folders → Create provider → Register provider → Register routes
→ Register migrations → Register views → Register configuration
→ Create repositories → Bind interfaces → Create services → Start development
```

### With libinkk/modular

```
Install package → Create module → Generate CRUD → Start building features
```

---

## Future Roadmap

### Version 1.0

- Module Generator
- Automatic Discovery
- Code Generators
- CRUD Generator

### Version 1.5

- Repository Automation
- Service Automation
- Module Cache

### Version 2.0

- Dependency Management
- Architecture Analyzer
- Project Health Checker

### Version 3.0

- AI-assisted Scaffold Generator
- OpenAPI Documentation Generator
- Module Dependency Graph
- Multi-Tenant Support

---

## Philosophy

A Laravel application should be organized by **business modules**, not by framework folders.

Every feature should be self-contained.

Every module should be:

- Independent
- Testable
- Maintainable
- Scalable
- Reusable

---

## Goal

**Install once. Generate modules. Focus only on business logic.**

Everything else should be handled automatically by `libinkk/modular`.
