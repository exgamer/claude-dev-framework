# HTTP

`MPS\Core\Http`

Слой HTTP состоит из четырёх компонентов: Actions, Controllers, Requests, Responses.

---

## Actions

`MPS\Core\Http\Actions\Action`

Единица бизнес-логики, привязанная к HTTP-запросу. Реализует метод `run()` или `__invoke()`.

```php
use MPS\Core\Http\Actions\Action;

class GetUserAction extends Action
{
    public function __construct(private readonly UserServiceInterface $service)
    {
        parent::__construct();
    }

    public function run(int $id): JsonResponse
    {
        $user = $this->service->findById($id);

        return response()->json($user);
    }
}
```

### Настройка Action

```php
$action->setFormRequestClass(UserRequest::class);   // привязать Request
$action->setResponseClass(UserResponse::class);     // привязать Response
$action->getRequest();                              // получить экземпляр Request
$action->getResponse($resource);                    // получить экземпляр Response
```

### Подключение в контроллере через actions()

Action можно не инстанциировать явно — контроллер создаёт их через `__call`:

```php
class UserController extends Controller
{
    protected function actions(): array
    {
        return [
            'show' => GetUserAction::class,
            // или с конфигом:
            'store' => ['className' => CreateUserAction::class],
        ];
    }
}
```

---

## Controllers

`MPS\Core\Http\Controllers\Controller`

Базовый контроллер. Расширяет Laravel `Controller`.

```php
use MPS\Core\Http\Controllers\Controller;

class UserController extends Controller
{
    public function show(int $id): JsonResponse
    {
        return app(GetUserAction::class)->run($id);
    }
}
```

Если контроллер объявляет `actions()`, то `__call` автоматически создаёт нужный Action и передаёт ему сервис через `setService()` / `getService()`.

---

## Requests

### FormRequest

`MPS\Core\Http\Requests\FormRequest`

Базовый Request. При ошибке валидации бросает `AppException` с типом `VALIDATION_ERROR` (а не стандартный `ValidationException`).

```php
use MPS\Core\Http\Requests\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }

    // значения по умолчанию — применяются если поле отсутствует в запросе
    public function defaults(): array
    {
        return [
            'role' => 'user',
        ];
    }
}
```

Дополнительные методы:
- `label(string $attribute)` — получить label из `attributes()`
- `paramsCount(): int` — количество непустых параметров

### CommandRequest

`MPS\Core\Http\Requests\CommandRequest`

Для POST/PUT/PATCH запросов. Валидационные данные берутся из тела (`post()`), не из query.

```php
class UpdateProfileRequest extends CommandRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
        ];
    }
}
```

### SearchRequest

`MPS\Core\Http\Requests\SearchRequest`

Для GET-запросов поиска. Автоматически добавляет правила для `page`, `per_page`, `sort`. Валидационные данные берутся из query.

```php
class UserSearchRequest extends SearchRequest
{
    protected array $sortAttributes = ['created_at', 'name', 'email'];

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', 'string'],
        ]);
    }
}
```

Параметр `sort` принимается как строка `"-created_at,name"` и автоматически разбивается в массив.

Переопределить или отключить стандартные правила:
```php
$this->disableDefaultRules();              // отключить все
$this->disableDefaultRules(['sort' => []]); // отключить только sort
```

### StatusChangeRequest

`MPS\Core\Http\Requests\StatusChangeRequest`

Для эндпоинтов смены статуса. Валидирует поле `status` (required, string, min:1, max:1).

```php
class OrderStatusRequest extends StatusChangeRequest
{
    protected function availableStatuses(): array
    {
        return array_column(OrderStatusEnum::cases(), 'value');
    }
}
```

### ByIdsSearchRequest

`MPS\Core\Http\Requests\ByIdsSearchRequest`

Поиск по массиву ID.

```php
// GET /users/by-ids?ids[]=1&ids[]=2&ids[]=3
// Правила: ids — required array, ids.* — integer
```

---

## Responses

### Response

`MPS\Core\Http\Responses\Response`

Базовый Response, расширяет Laravel `JsonResource`. Включает `MakeAwareTrait`.

```php
use MPS\Core\Http\Responses\Response;

class UserResponse extends Response
{
    public function toArray($request): array
    {
        return [
            'id'    => $this->resource->id,
            'name'  => $this->resource->name,
            'email' => $this->resource->email,
        ];
    }
}

// использование
return UserResponse::make($user);
```

`$preserveKeys = true` — ключи массива сохраняются при сборе коллекций.

### JsonResponse

`MPS\Core\Http\Responses\JsonResponse`

Алиас над `Response`. Семантически отделяет JSON-ответы. Функционально идентичен.
