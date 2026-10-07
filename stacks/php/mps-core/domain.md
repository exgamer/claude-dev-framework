# Создание домена и модуля

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## Создание нового домена

### 1. Структура директорий

Раскладка и чеклист — `../structure.md` (без `src/`, инфраструктура с типом хранилища: `Infrastructure/Postgres/{D}/…`). Ниже — только как пользоваться ядром.

### 2. Infrastructure ServiceProvider

```php
// Infrastructure/Postgres/{Domain}/Providers/ServiceProvider.php
namespace App\Infrastructure\YourDomain\Providers;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'infrastructure:your-domain';
    }
}
```

### 3. Зарегистрировать в DomainServiceProvider

```php
// ServiceProvider.php подпроекта (список провайдеров доменов).php
$this->registerServiceProviders([
    \App\Infrastructure\YourDomain\Providers\ServiceProvider::class,
]);
```

### 4. definitions.php

Живёт в `Infrastructure/Postgres/{Domain}/Providers/definitions.php`.
Интерфейсы из `Domains/`, реализации из `Infrastructure/`:

```php
use App\Domains\YourDomain\Modules\YourModule\Repositories\YourRepositoryInterface;
use App\Domains\YourDomain\Modules\YourModule\Services\YourServiceInterface;
use App\Domains\YourDomain\Modules\YourModule\Services\YourService;
use App\Infrastructure\YourDomain\YourModule\Repositories\YourRepository;

return [
    [
        'abstract' => YourRepositoryInterface::class,
        'concrete' => YourRepository::class,
    ],
    [
        'abstract' => YourServiceInterface::class,
        'concrete' => YourService::class,
    ],
];
```

---

## Создание модуля

### ServiceProvider модуля

Нужен **только если модуль регистрирует что-то дополнительно** (свои провайдеры, биндинги).
Большинство модулей ServiceProvider не имеют.

```php
namespace App\Domains\YourDomain\Modules\YourModule;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'your-domain:your-module';
    }
}
```
