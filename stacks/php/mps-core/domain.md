# Создание домена и модуля

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Создание нового домена

### 1. Структура директорий

Раскладка и чеклист — `../structure.md` (без `src/`, инфраструктура с типом хранилища: `Infrastructure/Postgres/{D}/…`). Ниже — только как пользоваться ядром.

### 2. Infrastructure ServiceProvider

```php
// Infrastructure/Postgres/{Domain}/Providers/ServiceProvider.php
namespace App\ParkingApp\Infrastructure\Postgres\YourDomain\Providers;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'infrastructure:your-domain';
    }
}
```

### 3. Зарегистрировать в корневом ServiceProvider подпроекта

```php
// ServiceProvider.php подпроекта
$this->registerServiceProviders([
    \App\ParkingApp\Domains\YourDomain\ServiceProvider::class,
    \App\ParkingApp\Infrastructure\Postgres\YourDomain\Providers\ServiceProvider::class,
]);
```

### 4. definitions.php — два файла

Сервисы домена — `Domains/{Domain}/definitions.php`, репозитории — `Infrastructure/Postgres/{Domain}/Providers/definitions.php` (`../conventions.md` п. 7). Интерфейсы — из `Domains/`, реализации репозиториев — из `Infrastructure/`.

```php
// Domains/{Domain}/definitions.php
use App\ParkingApp\Domains\YourDomain\Modules\YourModule\Services\YourCrudService;
use App\ParkingApp\Domains\YourDomain\Modules\YourModule\Services\YourCrudServiceInterface;

return [
    [
        'abstract' => YourCrudServiceInterface::class,
        'concrete' => YourCrudService::class,
    ],
];
```

```php
// Infrastructure/Postgres/{Domain}/Providers/definitions.php
use App\ParkingApp\Domains\YourDomain\Modules\YourModule\Repositories\YourRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\YourDomain\YourModule\Repositories\YourRepository;

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
namespace App\ParkingApp\Domains\YourDomain\Modules\YourModule;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'your-domain:your-module';
    }
}
```
