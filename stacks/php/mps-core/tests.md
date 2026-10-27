# Тесты

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Когда что использовать

Общие правила уровней и моков — `approaches/process/testing.md`; здесь — классы ядра и Laravel.

| Тип | Когда писать |
|---|---|
| Unit | Helpers, Traits, DataObjects, Services с мокированными зависимостями |
| Feature | HTTP-эндпоинты через приложение (маршрут, middleware, Request, ответ); с реальной БД — на базе проекта, если она есть |

**Правило:** Unit тест не обращается к БД и не делает HTTP-запросы. Если нужна БД, HTTP через приложение или реальные сервисы — это Feature тест.

## Структура

Раскладка тестов — `../structure.md`, раздел «Тесты» (решение O-3): `tests/Unit/{Подпроект}/…` с путём как у класса, `tests/Feature/{Подпроект}/{Context}/{Domain}/…`.

## Unit тест — выбор базового класса

| Ситуация | Базовый класс |
|---|---|
| Чистая логика без зависимостей (Helper, Trait, DataObject) | `PHPUnit\Framework\TestCase` |
| Нужен Laravel-контейнер или конфиг | `Tests\TestCase` |

```php
// Чистый Unit — Helper, Trait, DataObject
namespace Tests\Unit\YourApp\Core\Helpers;

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
// Unit с мокированием зависимостей — Service, Command; namespace и путь повторяют класс (../structure.md, «Тесты»)
namespace Tests\Unit\YourApp\Domains\YourDomain\Modules\YourModule\Services;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class YourServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration; // закрывает Mockery и засчитывает ожидания как проверки; ручной tearDown с Mockery::close() не нужен

    public function test_something(): void
    {
        $repository = Mockery::mock(YourRepositoryInterface::class);
        $repository->shouldReceive('oneById')->once()->with(1)->andReturn(new YourModel(['name' => 'Test']));

        $service = new YourService($repository);
        $result = $service->doSomething(1);

        $this->assertEquals('Test', $result->name);
    }
}
```

## Feature тест

HTTP через приложение: маршрут, middleware, Request, контроллер, обёртка ответа. В эталоне — `Tests\TestCase` (testbench) с моками сервисов и портов; Feature на реальной БД с откатом транзакции — только на базе проекта (`Tests\Feature\FeatureTestCase` и подобные), если она есть: её форма — по проекту.

```php
namespace Tests\Feature\YourApp\Admin\YourDomain;

use Mockery\MockInterface;
use Tests\TestCase;

class TariffsTest extends TestCase
{
    private TariffCrudServiceInterface&MockInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->mock(TariffCrudServiceInterface::class);   // остальные зависимости контроллера — тоже моками (эталон)
    }

    // URL — {подпроект}/{context}/… (../conventions.md п. 20); ответ — в обёртке success/data от api.response (п. 19)
    public function test_update(): void
    {
        $this->service->shouldReceive('update')
            ->once()
            ->andReturn($this->tariff());   // модель со всеми полями ответа, как tariff() в эталоне

        // тело — валидное по UpdateRequest (PUT — все поля, п. 12): без обязательного поля ответ — 422, сервис не вызывается
        $response = $this->json('PUT', '/parking/admin/tariffs/42', [
            'parking_id' => 15,
            'name' => 'Test',
            'currency' => 'KZT',
            'price' => 1500,
            'grace_minutes' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 42);
    }
}
```

Эталон — `../examples/tests/Feature/ParkingApp/Admin/Billing/TariffRouteTest.php` (моки, алиасы middleware хоста, неверный id пути — 422) и `../examples/tests/Feature/ParkingApp/Integration/Billing/TariffSignatureTest.php` (`assertJsonPath('data.id', …)`, ошибка — `assertJsonPath('success', false)`).

## Mock-классы

Mock — тестовая заглушка для **внешней интеграции** (адаптер внешней системы, платёжный шлюз). Интерфейс объявляет потребитель и называет по роли, без имени системы (`architecture/integrations.md`): источник данных — `Domains/{D}/Modules/{M}/Repositories/{Entity}RepositoryInterface.php`, отправка — `…NotifierInterface` (у домена или в папке workflow), реализация и мок — в инфраструктуре по имени внешней системы (`../structure.md`, `../conventions.md`, «Модели и внешние системы»):

```
Infrastructure/Http/{Provider}/{Product}/
├── {Provider}{Product}Repository.php     — реальная реализация (отправка — {Provider}{Product}Notifier.php)
└── Mocks/{Provider}{Product}RepositoryMock.php
```

**Не класть в `Domains/`** — домен не знает про инфраструктурные детали.
**Не класть в `tests/`** — Mock регистрируется в `definitions.php` и подключается автоматически в тестовом окружении, это не тестовый файл.

```php
namespace App\YourApp\Infrastructure\Http\YourProvider\YourProduct\Mocks;

use App\YourApp\Domains\YourDomain\Modules\YourModule\Repositories\YourProductRepositoryInterface;

class YourProviderYourProductRepositoryMock implements YourProductRepositoryInterface
{
    public function findStatus(int $id): ?string
    {
        return 'online';
    }
}
```

Регистрация — в `definitions.php` инфраструктуры рядом с реализацией:

```php
[
    'abstract' => YourProductRepositoryInterface::class,
    'concrete' => YourProviderYourProductRepository::class,
    'mock' => YourProviderYourProductRepositoryMock::class,
],
```

## Антипаттерны

```php
// ❌ Unit тест наследует FeatureTestCase — переместить в Feature/
namespace Tests\Unit\YourApp\Domains\YourDomain\Modules\YourModule\Services;

use Tests\Feature\FeatureTestCase; // ← нельзя в Unit/

class YourServiceTest extends FeatureTestCase { ... }
```
