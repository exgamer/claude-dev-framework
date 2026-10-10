# Тесты

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Когда что использовать

| Тип | Когда писать |
|---|---|
| Unit | Helpers, Traits, DataObjects, Services с мокированными зависимостями |
| Feature | HTTP-эндпоинты, интеграционные сценарии с реальной БД |

**Правило:** Unit тест не обращается к БД и не делает HTTP-запросы. Если нужна БД или реальные сервисы — это Feature тест.

## Структура

Раскладка тестов — `../structure.md`, раздел «Тесты» (решение O-3): `tests/Unit/{Подпроект}/…` с путём как у класса, `tests/Feature/{Подпроект}/{Context}/{Domain}/…`.

## Unit тест — выбор базового класса

| Ситуация | Базовый класс |
|---|---|
| Чистая логика без зависимостей (Helper, Trait, DataObject) | `PHPUnit\Framework\TestCase` |
| Нужен Laravel-контейнер или конфиг | `Tests\TestCase` |

```php
// Чистый Unit — Helper, Trait, DataObject
namespace Tests\Unit\Domains\YourDomain\Modules\YourModule;

use PHPUnit\Framework\TestCase;

class YourHelperTest extends TestCase
{
    public function test_normalize_works(): void
    {
        $this->assertEquals('expected', YourHelper::normalize('input'));
    }
}
```

```php
// Unit с мокированием зависимостей — Service, Command
namespace Tests\Unit\Domains\YourDomain\Modules\YourModule;

use Mockery;
use PHPUnit\Framework\TestCase;

class YourServiceTest extends TestCase
{
    public function test_something(): void
    {
        $repository = Mockery::mock(YourRepositoryInterface::class);
        $repository->shouldReceive('oneById')->once()->with(1)->andReturn(new YourModel(['name' => 'Test']));

        $service = new YourService($repository);
        $result = $service->doSomething(1);

        $this->assertEquals('Test', $result->name);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

## Feature тест

Полный стек с реальной БД. Каждый тест откатывается в транзакцию.

```php
namespace Tests\Feature\Entrypoints\Admin\YourDomain\YourModule;

use Tests\Feature\FeatureTestCase;

class PositiveTest extends FeatureTestCase
{
    public function test_create(): void
    {
        $response = $this->json('POST', '/api/your-domain/admin/your-module', [
            'name' => 'Test',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'status']);
    }

    public function test_index(): void
    {
        $response = $this->json('GET', '/api/your-domain/admin/your-module');

        $response->assertStatus(200)
            ->assertJsonStructure(['items', 'pagination']);
    }
}
```

## Mock-классы

Mock — тестовая заглушка для **внешней интеграции** (HTTP-клиент, внешнее API, платёжный шлюз).

**Где живёт:** `Infrastructure/{Тип}/{Domain}/{Module}/Mocks/`

```
Infrastructure/{Тип}/{Domain}/{Module}/
├── Integrations/
│   └── ExternalService.php       — реальная реализация
└── Mocks/
    └── ExternalServiceMock.php   — заглушка для тестов
```

**Не класть в `Domains/`** — домен не знает про инфраструктурные детали.
**Не класть в `tests/`** — Mock регистрируется в `definitions.php` и подключается автоматически, это не тестовый файл.

```php
namespace App\Infrastructure\YourDomain\YourModule\Mocks;

use App\Domains\YourDomain\Modules\YourModule\Interfaces\ExternalServiceInterface;

class ExternalServiceMock implements ExternalServiceInterface
{
    public function send(array $data): bool
    {
        return true;
    }
}
```

Регистрация в `Infrastructure/Postgres/{Domain}/Providers/definitions.php`:

```php
[
    'abstract' => ExternalServiceInterface::class,
    'concrete' => ExternalService::class,
    'mock'     => ExternalServiceMock::class,
    'alias'    => 'external-service',
],
```

## Антипаттерны

```php
// ❌ Unit тест наследует FeatureTestCase — переместить в Feature/
namespace Tests\Unit\Domains\Catalog\Modules\ProductOffer;

use Tests\Feature\FeatureTestCase; // ← нельзя в Unit/

class ProductOfferServiceTest extends FeatureTestCase { ... }
```
