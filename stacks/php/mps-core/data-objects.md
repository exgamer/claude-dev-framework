# DataObjects

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## DataObject

Базовый класс для доменных DTO. Свойства `private/protected`, fluent-сеттеры, геттеры.

```php
namespace App\Domains\YourDomain\Modules\YourModule\DataObjects;

use MPS\Core\DataObjects\DataObject;

class YourDto extends DataObject
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
$dto = YourDto::make();

// Маппинг массива → объект через рефлексию
$dto = YourDto::make()->fromArray($request->validated());

// Сериализация обратно в массив — не для передачи между слоями: в сервис и репозиторий уходит сам DTO (../conventions.md п. 11, 11a)
$dto->toArray();                  // ключи как есть
$dto->toArrayWithSnakeKeys();     // camelCase → snake_case
$dto->toArrayWithCamelKeys();
```

`fromArray()` обрабатывает типы автоматически: `BackedEnum` через `tryFrom()`, вложенный `DataObject` рекурсивно, `Carbon` из строки, неизвестные ключи — молча игнорируются.

**Поле с фиксированным набором значений — сразу enum.** Свойство и аргумент сеттера типизируются enum-ом (`?TariffStatusEnum`, `setStatus(TariffStatusEnum|null $value)`), не `?string`: `fromArray()` сам превращает строку из запроса в enum через `tryFrom()`, `toArray*()` отдаёт `->value`, сравнение — `=== TariffStatusEnum::ACTIVE`, без `->value`. Неизвестное значение `tryFrom()` даёт `null` — допустимые значения проверяет Request (`Rule::enum()`).

**Строки из multipart/query.** `fromArray()` вызывает сеттер из `InstanceHelper`, объявленного без `strict_types`, — PHP приводит скаляры сам: `'7'` → `?int` 7, `'2.5'` → `?float` 2.5. Поэтому тип аргумента сеттера = тип свойства (`?int`, `?float`), без `int|string` и ручного `(int)`. Нечисловая строка даёт `TypeError`, ядро пишет warning в лог и **молча пропускает поле** — формат числа проверяет Request (`integer`, `numeric`). Проверено на core 1.10.2 (AINA-2628); при обновлении ядра — тест вида «строка из формы доходит числом».

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

Типизированная коллекция `DataObject`-ов. Элементы приходят из массивов и гидрируются в объекты. Имя — `{Entity}ItemsDto` (как `PermissionItemsDto` в `parking_app`), `final`, `@extends DataObjectCollection<{Entity}Dto>`.

```php
/**
 * @extends DataObjectCollection<YourItemDto>
 */
final class YourItemsDto extends DataObjectCollection
{
    // Единственное требование — реализовать hydrate()
    protected function hydrate(array $item): YourItemDto
    {
        return YourItemDto::make()->fromArray($item);
    }
}
```

**Вложенная коллекция в DTO** (`buildings: [{building_id, …}]`) — свойство и сеттер типизированы коллекцией, сеттер без логики: `fromArray()` сам соберёт `YourItemsDto` из списка. Сборка вложенных DTO в сеттере (`setBuildings(?array)` с `fromArray` по элементам) — нет: исключение сеттера ядро глотает, поле молча остаётся `null` (AINA-2978).

```php
private ?YourItemsDto $buildings = null;

public function setBuildings(?YourItemsDto $value): self
{
    $this->buildings = $value;

    return $this;
}
```

Ядро (core 1.6.0) гидрирует коллекцию только из **списка** (`array_is_list`): ассоциативный массив `{"a": {...}}` → пустая коллекция, элемент не-массив (`[5]`) молча пропускается. Если пустая коллекция значит «отвязать всё», это разрушающий тихий отказ — поэтому Request обязан: поле — `list` (не только `array`, он пропускает ассоциативный), элементы — `'buildings.*' => ['array']` (`security.md`, «Меры по слоям»). При обновлении ядра — тест «не-список / не-массив → 422».

**Использование:**

```php
$collection = YourItemsDto::make()->setItems($rawArray);  // гидрирует каждый элемент

$collection->pushItem(['name' => 'foo']);   // array → hydrate() → объект
$collection->pushItem($yourItem);           // уже готовый объект — напрямую

$collection->getItems();   // YourItemDto[]
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
