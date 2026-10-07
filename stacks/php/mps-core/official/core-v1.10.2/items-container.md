# ItemsContainer

`MPS\Core\Components\ItemsContainer`

Типизированный контейнер для коллекций объектов. Итерируемый, поддерживает `toArray()`.

## ItemsContainer

Базовый контейнер. Используется напрямую или как основа для типизированных коллекций.

```php
use MPS\Core\Components\ItemsContainer\ItemsContainer;

$container = new ItemsContainer();
$container->setItems([1, 2, 3]);
$container->getItems(); // [1, 2, 3]

foreach ($container as $item) {
    // итерируемый
}
```

### Типизированный контейнер примитивов

Расширить `ItemsContainer` и добавить `@property` аннотацию — IDE будет знать тип элементов:

```php
/**
 * @property string[] $items
 */
class Images extends ItemsContainer
{
}

$images = Images::make();
$images->setItems([
    'https://example.com/image1.jpg',
    'https://example.com/image2.jpg',
]);

// аналог через fromArray
$images = Images::make()->fromArray($data);
```

## ItemsContainerTrait

Трейт с реализацией контейнера. Используется в `DataObjectCollection` и `ItemsContainer`.

```php
use MPS\Core\Components\ItemsContainer\ItemsContainerTrait;

class ProductIdCollection implements ItemsContainerInterface
{
    use ItemsContainerTrait;

    protected array $items = [];
}

$collection = new ProductIdCollection();
$collection->setItems([10, 20, 30]);
$collection->getItems();      // [10, 20, 30]
$collection->fresh();         // очистить
$collection->toArray();       // массив элементов
```

### Методы

```php
setItems(iterable $items, bool $fresh = true): static  // установить элементы
getItems(): array                                       // получить элементы
fresh(): void                                          // очистить
toArray(): array                                       // сериализовать
getIterator(): ArrayIterator                           // итератор
```

При `$fresh = true` (по умолчанию) перезаписывает существующие элементы. При `$fresh = false` — добавляет к существующим.

## Отличие от DataObjectCollection

| | `ItemsContainer` | `DataObjectCollection` |
|---|---|---|
| Назначение | Контейнер произвольных значений | Контейнер DataObject-ов |
| Гидрация из массива | Нет | Да, через `hydrate()` |
| Базовый класс | — | `DataObject` |
| `fromArray()` | Нет | Да (через DataObject) |
| `pushItem()` | Нет | Да (с автогидрацией) |

Для коллекций DataObject-ов использовать `DataObjectCollection` (см. [data-objects.md](data-objects.md)).
`ItemsContainer` — для примитивов, ID, или объектов без DTO-контракта.

## Устаревший вариант из mps/utils

> `MPS\Utils\Components\ItemsContainer\ItemsContainer` — deprecated с версии 2.2.1.
> Использовать `MPS\Core\Components\ItemsContainer\ItemsContainer`.

```php
// ❌ Устарело
use MPS\Utils\Components\ItemsContainer\ItemsContainer;
use MPS\Utils\Components\ItemsContainer\ItemsContainerAwareTrait;

// ✅ Актуально
use MPS\Core\Components\ItemsContainer\ItemsContainer;
use MPS\Core\Components\ItemsContainer\ItemsContainerTrait;
```

API идентичен.
