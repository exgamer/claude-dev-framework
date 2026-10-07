# Traits

`MPS\Core\Traits`

---

## ArrayableTrait

Сериализация объекта в массив. Используется в `DataObject`.

```php
use MPS\Core\Traits\ArrayableTrait;

class MyObject
{
    use ArrayableTrait;

    public string $firstName = 'John';
    public string $lastName  = 'Doe';
}

$obj = new MyObject();
$obj->toArray();                  // ['firstName' => 'John', 'lastName' => 'Doe']
$obj->toArrayWithSnakeKeys();     // ['first_name' => 'John', 'last_name' => 'Doe']
$obj->toArrayWithCamelKeys();     // ['firstName' => 'John', 'lastName' => 'Doe']
$obj->toArray(StringCaseEnum::KEBAB); // ['first-name' => 'John', 'last-name' => 'Doe']
```

Рекурсивная обработка значений:
- `BackedEnum` → `$enum->value`
- `UnitEnum` → `$enum->name`
- `CarbonInterface` → `toDateTimeString()`
- вложенный `DataObject` (реализует `MPS\Core\Interfaces\Arrayable`) → рекурсивно
- Laravel `Arrayable` → `toArray()`
- `ArrayObject` → `getArrayCopy()`

---

## ExpandableTrait

Управление eager-loading через строковые маркеры. Используется в сервисном слое.

```php
use MPS\Core\Traits\ExpandableTrait;

class UserService
{
    use ExpandableTrait;

    public function getUser(int $id): User
    {
        $user = $this->repository->find($id);

        if ($this->hasExpand('roles')) {
            $user->load('roles');
        }

        return $user;
    }
}

// в контроллере
$service->expand('roles')->expand('permissions');
$user = $service->getUser($id);
```

### Методы

```php
$service->expand('roles'): self                  // добавить expand
$service->expandCallable(fn() => ['roles']): self // добавить через callable
$service->setExpanded(['roles', 'permissions']): self // установить весь список
$service->getExpanded(): array                    // получить список
$service->hasExpand('roles'): bool               // проверить наличие
$service->clearExpanded(): self                   // очистить
```

> **Ограничение**: строковые маркеры без типизации, опечатки не обнаруживаются. IDE-поддержки нет. Рекомендуется заменить на enum-подход в новом коде.

---

## MakeAwareTrait

Статический конструктор `make()`. Используется в `DataObject`, `Response`.

```php
use MPS\Core\Traits\MakeAwareTrait;

class UserResponse
{
    use MakeAwareTrait;

    public function __construct(private readonly User $user) {}
}

$response = UserResponse::make($user);
// эквивалентно new UserResponse($user)
```

---

## ValidatableTrait

Валидация объекта через Laravel Validator. Требует `ArrayableTrait` для сериализации.

```php
use MPS\Core\Traits\ArrayableTrait;
use MPS\Core\Traits\ValidatableTrait;

class CreateOrderDTO
{
    use ArrayableTrait;
    use ValidatableTrait;

    public int $userId;
    public array $items = [];

    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'items'   => ['required', 'array', 'min:1'],
        ];
    }

    protected function messages(): array
    {
        return [
            'items.min' => 'Order must have at least one item.',
        ];
    }

    protected function attributes(): array
    {
        return [
            'user_id' => 'User',
        ];
    }
}

$dto = new CreateOrderDTO();
$dto->userId = 1;
$dto->items  = [];

if (! $dto->validated()) {
    $errors = $dto->getValidationErrors(); // MessageBag
}
```

По умолчанию данные сериализуются в `snake_case` перед передачей в Validator (`StringCaseEnum::SNAKE`). Можно переопределить:

```php
$dto->validated(StringCaseEnum::CAMEL);
```
