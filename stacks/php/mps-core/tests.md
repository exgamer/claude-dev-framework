# Тесты

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Когда что использовать

Общие правила уровней и моков — `approaches/process/testing.md`; здесь — классы ядра и Laravel.

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
namespace Tests\Unit\ParkingApp\Core\Helpers;

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
namespace Tests\Unit\ParkingApp\Domains\YourDomain\YourModule;

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

Полный стек с реальной БД. Каждый тест откатывается в транзакцию.

```php
namespace Tests\Feature\ParkingApp\Admin\YourDomain;

use Tests\Feature\FeatureTestCase;

class TariffsTest extends FeatureTestCase
{
    // URL — {подпроект}/{context}/… (../conventions.md п. 20); ответ — в обёртке success/data от api.response (п. 19)
    public function test_create(): void
    {
        $response = $this->json('POST', '/parking/admin/tariffs', [
            'parking_id' => 15,
            'name' => 'Test',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'status']]);
    }

    public function test_index(): void
    {
        $response = $this->json('GET', '/parking/admin/tariffs');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['items', 'pagination']]);
    }
}
```

Эталон — `../examples/tests/Feature/ParkingApp/Integration/Billing/TariffSignatureTest.php` (`assertJsonPath('data.id', …)`, ошибка — `assertJsonPath('success', false)`).

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
namespace App\ParkingApp\Infrastructure\Http\Lpm\Camera\Mocks;

use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\CameraRepositoryInterface;

class LpmCameraRepositoryMock implements CameraRepositoryInterface
{
    public function findStatus(int $cameraId): ?string
    {
        return 'online';
    }
}
```

Регистрация — в `definitions.php` инфраструктуры рядом с реализацией:

```php
[
    'abstract' => CameraRepositoryInterface::class,
    'concrete' => LpmCameraRepository::class,
    'mock'     => LpmCameraRepositoryMock::class,
],
```

## Антипаттерны

```php
// ❌ Unit тест наследует FeatureTestCase — переместить в Feature/
namespace Tests\Unit\ParkingApp\Domains\Catalog\ProductOffer;

use Tests\Feature\FeatureTestCase; // ← нельзя в Unit/

class ProductOfferServiceTest extends FeatureTestCase { ... }
```
