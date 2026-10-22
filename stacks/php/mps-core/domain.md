# Создание домена и модуля

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Создание нового домена

### 1. Структура директорий

Раскладка и чеклист — `../structure.md` (без `src/`, инфраструктура с типом хранилища: `Infrastructure/Postgres/{D}/…`). Ниже — только как пользоваться ядром.

### 2. Infrastructure ServiceProvider

```php
// Infrastructure/Postgres/{Domain}/Providers/ServiceProvider.php
namespace App\YourApp\Infrastructure\Postgres\YourDomain\Providers;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает definitions.php (интерфейсы репозиториев YourDomain → Postgres) и миграции таблиц домена.
 *
 * @author …
 */
class ServiceProvider extends CoreServiceProvider
{
}
```

Провайдер пустой: `definitions.php` и `database/migrations/` рядом с ним ядро подхватывает само; `getName()` ядра не переопределяется — в эталоне провайдеры без него. Так же — провайдер домена (`Domains/{Domain}/ServiceProvider.php` наследует `Core/Providers/ServiceProvider` подпроекта) и провайдеры точек входа.

### 3. Зарегистрировать в корневом ServiceProvider подпроекта

Корневой провайдер держит списки провайдеров по слоям и регистрирует их `$this->app->register()`; провайдеры инфраструктуры собирает агрегатор `Infrastructure/ServiceProvider.php` той же формы. Эталон — `../examples/parking_app/ServiceProvider.php`, `../examples/parking_app/Infrastructure/ServiceProvider.php`.

```php
// ServiceProvider.php подпроекта
/**
 * @var list<class-string<BaseServiceProvider>>
 */
private array $domainProviders = [
    Domains\Billing\ServiceProvider::class,
    Domains\YourDomain\ServiceProvider::class,
];

public function register(): void
{
    foreach ($this->domainProviders as $provider) {
        $this->app->register($provider);
    }

    // так же — $infrastructureProviders, $entrypointProviders, $utilsProviders
}

// Infrastructure/ServiceProvider.php
private array $providers = [
    Postgres\Billing\Providers\ServiceProvider::class,
    Postgres\YourDomain\Providers\ServiceProvider::class,
];
```

### 4. definitions.php — два файла

Сервисы домена — `Domains/{Domain}/definitions.php`, репозитории — `Infrastructure/Postgres/{Domain}/Providers/definitions.php` (`../conventions.md` п. 7). Интерфейсы — из `Domains/`, реализации репозиториев — из `Infrastructure/`.

```php
// Domains/{Domain}/definitions.php
use App\YourApp\Domains\YourDomain\Modules\YourModule\Services\YourCrudService;
use App\YourApp\Domains\YourDomain\Modules\YourModule\Services\YourCrudServiceInterface;

return [
    [
        'abstract' => YourCrudServiceInterface::class,
        'concrete' => YourCrudService::class,
    ],
];
```

```php
// Infrastructure/Postgres/{Domain}/Providers/definitions.php
use App\YourApp\Domains\YourDomain\Modules\YourModule\Repositories\YourRepositoryInterface;
use App\YourApp\Infrastructure\Postgres\YourDomain\YourModule\Repositories\YourRepository;

return [
    [
        'abstract' => YourRepositoryInterface::class,
        'concrete' => YourRepository::class,
    ],
];
```

---

## Создание модуля

### ServiceProvider модуля

Нужен **только если модуль регистрирует что-то дополнительно** (свои провайдеры, биндинги).
Большинство модулей ServiceProvider не имеют.

```php
namespace App\YourApp\Domains\YourDomain\Modules\YourModule;

use App\YourApp\Core\Providers\ServiceProvider as Base;

class ServiceProvider extends Base
{
    // только то, что модуль регистрирует сверх definitions.php домена; getName() не переопределяется
}
```
