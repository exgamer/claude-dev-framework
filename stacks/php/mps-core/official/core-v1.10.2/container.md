## Application

`MPS\Core\Components\Container\Application` расширяет `Illuminate\Foundation\Application` и реализует `ApplicationInterface`.

### swapper()

Ленивая инициализация `ContainerSwapper`:

```php
$this->app->swapper()->runWith(...);
```

### captureBinding / restoreBinding

Низкоуровневые методы, используемые `ContainerSwapper` внутри. Снимают и восстанавливают полное состояние одного биндинга (binding, instance, resolved, extenders).

```php
$snapshot = $this->app->captureBinding(SomeService::class);
// ... изменения в контейнере ...
$this->app->restoreBinding($snapshot);
```

---

## Использование в тестах

Основной сценарий — подмена зависимости на время одного теста без ручного teardown:

```php
public function test_charges_with_fake_gateway(): void
{
    $this->app->swapper()->runWith(
        PaymentGateway::class,
        new FakePaymentGateway(shouldFail: false),
        function () {
            $result = $this->app->make(PaymentService::class)->charge(100);
            expect($result->success)->toBeTrue();
        }
    );
}
```

Исходный биндинг восстанавливается автоматически, в том числе при упавшем тесте.

---

# Container

`MPS\Core\Components\Container`

Утилиты для управления биндингами Laravel-контейнера: декларативный DI-биндинг и временная подмена зависимостей с автоматическим откатом.

---

## DefinitionHelper

`MPS\Core\Components\Container\Helpers\DefinitionHelper`

Декларативный DI-биндинг через PHP-массив.

```php
use MPS\Core\Components\Container\Helpers\DefinitionHelper;

DefinitionHelper::resolve($application, $definitions);
```

### Формат definitions.php

```php
return [
    // простой singleton (строка — сам себе биндит)
    SomeClass::class,

    // singleton с маппингом интерфейс → класс
    [
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
    ],

    // с mock-подменой в тестах
    [
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
        'mock'      => MockSomeClass::class,
    ],

    // bind вместо singleton
    [
        'bindType'  => \MPS\Core\Components\Container\Enums\BindTypeEnum::BIND, // 'bind'
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
    ],

    // с алиасом
    [
        'abstract'  => SomeInterface::class,
        'concrete'  => SomeClass::class,
        'alias'     => 'some-alias',
    ],
];
```

Поддерживаемые `bindType`: `singleton` (по умолчанию), `bind`, `scoped`, `singletonIf`, `bindIf`, `scopedIf`.

---

## ContainerSwapper

Позволяет временно заменить биндинг в контейнере, выполнить callback и гарантированно восстановить исходное состояние — даже при исключении.

Получить экземпляр через `Application`:

```php
$swapper = $this->app->swapper();
```

### runWith

Временная подмена с автоматическим откатом.

```php
$swapper->runWith(string $abstract, mixed $replacement, callable $callback): mixed
```

`$replacement` может быть конкретным объектом или замыканием:

```php
// конкретный объект — биндится как instance
$swapper->runWith(
    PaymentGateway::class,
    new FakePaymentGateway(),
    function () {
        // внутри callback app()->make(PaymentGateway::class) вернёт FakePaymentGateway
        $this->service->charge(100);
    }
);

// замыкание — биндится как singleton, кэш сбрасывается
$swapper->runWith(
    CurrencyConverter::class,
    fn () => new FixedRateConverter(rate: 1.0),
    function () {
        // ...
    }
);
```

Callback может возвращать значение:

```php
$result = $swapper->runWith(
    CurrencyConverter::class,
    new FixedRateConverter(rate: 1.0),
    fn () => $this->service->convert(100, 'USD', 'KZT')
);
```

После выхода из callback — биндинг восстанавливается. Если abstract не был зарегистрирован до вызова, он полностью удаляется из контейнера.
