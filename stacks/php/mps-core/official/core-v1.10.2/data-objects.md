# DataObjects

`MPS\Core\DataObjects`

## DataObject

Базовый абстрактный DTO. Наследовать для всех объектов передачи данных.

```php
use MPS\Core\DataObjects\DataObject;

class UserDTO extends DataObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $age = null,
    ) {}
}
```

Включает трейты:
- `ArrayableTrait` — `toArray()`, `toArrayWithSnakeKeys()`, `toArrayWithCamelKeys()`
- `ValidatableTrait` — `validated()`, `getValidationErrors()`
- `MakeAwareTrait` — статический конструктор `make(...$params)`

### fromArray

Заполняет объект из массива через `InstanceHelper::objectFromArray`. Обрабатывает вложенные DataObject, Enum, Carbon.

```php
$dto = new UserDTO('', '');
$dto->fromArray(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
```

### make

Статический конструктор — сокращение для `new static(...)`.

```php
$dto = UserDTO::make('John', 'john@example.com');
```

### toArray

```php
$dto->toArray();                       // ключи как есть (camelCase)
$dto->toArrayWithSnakeKeys();          // ключи в snake_case
$dto->toArrayWithCamelKeys();          // ключи в camelCase
$dto->toArray(StringCaseEnum::KEBAB);  // ключи в kebab-case
```

Рекурсивно разворачивает вложенные DataObject, Enum (по значению для BackedEnum, по имени для UnitEnum), Carbon → строка.

### validated

Валидация через Laravel Validator. Требует реализации `rules()`.

```php
class CreateOrderDTO extends DataObject
{
    public function __construct(
        public readonly int $userId,
        public readonly array $items,
    ) {}

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'items'   => ['required', 'array', 'min:1'],
        ];
    }
}

$dto = CreateOrderDTO::make($userId, $items);

if (! $dto->validated()) {
    throw new ValidationException($dto->getValidationErrors());
}
```

---

## DataObjectCollection

Абстрактный типизированный контейнер DataObject-ов. Наследовать и реализовать `hydrate()`.

```php
use MPS\Core\DataObjects\DataObjectCollection;

class UserDTOCollection extends DataObjectCollection
{
    protected function hydrate(array $item): UserDTO
    {
        return UserDTO::make($item['name'], $item['email'], $item['age'] ?? null);
    }
}
```

### Методы

```php
$collection = new UserDTOCollection();
$collection->setItems($arrayOfItems);   // заменить все элементы
$collection->pushItem($userDto);        // добавить один элемент (DTO или массив)
$collection->getItems(): array;         // вернуть все элементы
$collection->toArray();                 // сериализовать в массив

foreach ($collection as $item) { ... } // итерируемый
```

`pushItem` принимает как готовый `DataObjectInterface`, так и массив — в этом случае вызывает `hydrate()`.

### Вложенные коллекции в DataObject

Паттерн для DataObject с вложенными `ItemsContainer` и `DataObjectCollection`. Сеттер принимает как массив, так и готовый объект — конвертирует на лету:

```php
class Product extends DataObject
{
    protected Images $images;           // ItemsContainer
    protected PricesCollection $prices; // DataObjectCollection

    public function __construct()
    {
        $this->images = Images::make();
        $this->prices = PricesCollection::make();
    }

    public function setImages(array|Images $images): self
    {
        if (is_array($images)) {
            $images = Images::make()->setItems($images);
        }

        $this->images = $images;

        return $this;
    }

    public function setPrices(array|PricesCollection $prices): self
    {
        if (is_array($prices)) {
            $prices = PricesCollection::make()->fromArray($prices);
        }

        $this->prices = $prices;

        return $this;
    }
}
```

`InstanceHelper::objectFromArray` найдёт сеттеры `setImages` и `setPrices` автоматически — поэтому `Product::make()->fromArray($data)` будет работать корректно даже с вложенными коллекциями.

---

## Гидрация через InstanceHelper

`InstanceHelper::objectFromArray` — центральный механизм заполнения объектов. Используется внутри `DataObject::fromArray`.

### Правила маппинга

- Ключи массива матчатся к свойствам объекта **без учёта регистра и подчёркиваний** (`user_id` → `userId`, `UserID` → `userId`)
- Если есть сеттер `setPropertyName()` — вызывается он
- Иначе — свойство заполняется напрямую через Reflection
- `null` при допускающем `null` типе — присваивается без обработки

### Поддерживаемые типы

| Входящее значение | Тип свойства | Результат |
|---|---|---|
| строка | `Carbon` | `Carbon::parse($value)` |
| `int\|string` | `BackedEnum` | `Enum::tryFrom($value)` |
| строка | `UnitEnum` | поиск по `$case->name` |
| массив | `DataObject` | рекурсивный `createObject()` |
| массив | `ItemsContainer` | оборачивается в `['items' => $value]` |

Ошибки несоответствия типов логируются через `Log::warning` и игнорируются — свойство остаётся незаполненным.

### Пример вложенной гидрации

```php
class TestClass extends DataObject
{
    private string $testProperty;
}

class ParentProductItem extends DataObject
{
    private int $id;
    private ?int $legacyProductId = null;
    private ?TestClass $testClass = null;
}

class OfferItem extends DataObject
{
    private int $id;
    private int $supplierId;
    private ?ParentProductItem $parentProductItem = null;
}

$data = [
    'id'          => 10001,
    'supplier_id' => 101,
    'parent_product_item' => [
        'id'                 => 12345,
        'legacy_product_id'  => 54321,
        'test_class'         => [
            'test_property' => 'value',
        ],
    ],
];

$dto = InstanceHelper::createObject($data, OfferItem::class);
// $dto->id = 10001
// $dto->parentProductItem->id = 12345
// $dto->parentProductItem->testClass->testProperty = 'value'
```

Гидрация рекурсивна — глубина не ограничена. Ключи массива нечувствительны к регистру и подчёркиваниям (`supplier_id` → `supplierId`).
