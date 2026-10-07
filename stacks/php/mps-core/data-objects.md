# DataObjects

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## DataObject

Базовый класс для доменных DTO. Свойства `private/protected`, fluent-сеттеры, геттеры.

```php
namespace App\Domains\YourDomain\Modules\YourModule\DataObjects;

use MPS\Core\DataObjects\DataObject;

class YourDTO extends DataObject
{
    private ?string $name = null;
    private ?int $status = null;
    private ?Price $price = null;      // вложенный DataObject

    public function getName(): ?string { return $this->name; }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setPrice(array|Price|null $price): self
    {
        if (is_array($price)) {
            $price = Price::make()->fromArray($price);
        }

        $this->price = $price;

        return $this;
    }
}
```

**Методы из коробки:**

```php
// Фабричный метод
$dto = YourDTO::make();

// Маппинг массива → объект через рефлексию
$dto = YourDTO::make()->fromArray($request->validated());

// Сериализация обратно в массив
$dto->toArray();                  // ключи как есть
$dto->toArrayWithSnakeKeys();     // camelCase → snake_case
$dto->toArrayWithCamelKeys();
```

`fromArray()` обрабатывает типы автоматически: `BackedEnum` через `tryFrom()`, вложенный `DataObject` рекурсивно, `Carbon` из строки, неизвестные ключи — молча игнорируются.

**Встроенная валидация (опционально):**

```php
protected function rules(): array
{
    return ['name' => ['required', 'string', 'max:255']];
}

// Использование
if (! $dto->validated()) {
    throw new ValidationAppException($dto->getValidationErrors()->toArray());
}
```

---

## DataObjectCollection

Типизированная коллекция `DataObject`-ов. Элементы приходят из массивов и гидрируются в объекты.

```php
class YourItemCollection extends DataObjectCollection
{
    // Единственное требование — реализовать hydrate()
    protected function hydrate(array $item): YourItem
    {
        return YourItem::make()->fromArray($item);
    }
}
```

**Использование:**

```php
$collection = YourItemCollection::make()->setItems($rawArray);  // гидрирует каждый элемент

$collection->pushItem(['name' => 'foo']);   // array → hydrate() → объект
$collection->pushItem($yourItem);           // уже готовый объект — напрямую

$collection->getItems();   // YourItem[]
$collection->fresh();      // очистить
$collection->toArray();    // массив массивов

foreach ($collection as $item) { ... }  // IteratorAggregate
```

---

## ItemsContainer

Контейнер для уже типизированных объектов (нет `hydrate()`). Используется когда элементы — интерфейсы или разнотипные объекты.

```php
use MPS\Core\Components\ItemsContainer\ItemsContainer;

class ParseItems extends ItemsContainer
{
    public function pushItem(ParseItemInterface $item): static
    {
        $this->items[] = $item;
        return $this;
    }
}
```

API идентично `DataObjectCollection`: `setItems`, `getItems`, `fresh`, `toArray`, `IteratorAggregate`.

---

## Когда что использовать

| Ситуация | Класс |
|---|---|
| Одиночный DTO с полями | `DataObject` |
| Коллекция, элементы приходят из `array[]` (импорт, API) | `DataObjectCollection` |
| Коллекция, элементы — уже объекты / интерфейсы | `ItemsContainer` |
