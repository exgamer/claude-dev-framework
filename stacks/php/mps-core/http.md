# HTTP слой (Entrypoints)

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## Структура

Раскладка — `../structure.md`: `Entrypoints/{Context}/{Domain}/{Module}/Http/{Controllers,Requests/{Entity},Responses}/`, `routes.php` и `ServiceProvider.php` — на уровне `Entrypoints/{Context}/{Domain}/`. Имена Request — `Requests/{Entity}/{Create,Update,Index}Request` (P-5). Эталон — `../examples/`.

## Controller

```php
use MPS\Core\Components\CRUD\Http\Controllers\CRUDController;

class YourController extends CRUDController
{
    public function __construct(
        protected YourServiceInterface $service,
    ) {}

    public function index(YourSearchRequest $request): JsonResponse
    {
        $dto = (new SearchDataObject())->setParams($request->validated());
        $result = $this->service->search($dto);

        return response()->json(YourPaginateResponse::make($result));
    }

    public function view(int $id): JsonResponse
    {
        $result = $this->service->searchById($id);

        return response()->json(YourResponse::make($result));
    }

    // Запись — DTO, не массив (../conventions.md п. 11a, P-18)
    public function create(YourCreateRequest $request): JsonResponse
    {
        $dto = YourDto::make()->fromArray($request->validated());

        return response()->json(new YourResponse($this->service->create($dto)), Response::HTTP_CREATED);
    }

    public function update(int $id, YourUpdateRequest $request): JsonResponse
    {
        $dto = YourDto::make()->fromArray($request->validated());

        return response()->json(new YourResponse($this->service->update($id, $dto)));
    }

    public function delete(int $id): JsonResponse
    {
        $this->service->deleteById($id);

        return response()->json([], 204);
    }
}
```

## Requests

```php
// Create/Update
use MPS\Core\Http\Requests\CommandRequest;

class YourCreateRequest extends CommandRequest
{
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:255'],
            'status' => ['integer', Rule::in(YourStatusEnum::values())],
        ];
    }
    
    public function default(): array
    {
        return [
            'name' => 'foo',
        ];
    }
}

// Search
use Illuminate\Validation\Rule;
use MPS\Core\Http\Requests\SearchRequest;

class YourSearchRequest extends SearchRequest
{
    public function rules(): array
    {
        return [
            'page'     => ['integer', 'min:1'],
            'per_page' => ['integer', 'between:1,100'],
            'id'       => ['integer', 'gt:0'],
            'name'     => ['string', 'max:255'],
            'status'   => [Rule::enum(YourStatusEnum::class)],
            'sort'     => ['array', 'max:5'],
        ];
    }
}
```

## Routes

```php
// Entrypoints/Admin/{Domain}/routes.php
use Illuminate\Support\Facades\Route;

Route::name('admin.your-domain.your-module.')
    ->prefix('api/admin/your-domain/your-module')
    ->middleware(['api', 'auth.admin'])
    ->group(function () {
        Route::get('/', [YourController::class, 'index'])
            ->setLabel('Список')
            ->name('index');

        Route::get('/{id}', [YourController::class, 'view'])
            ->setLabel('Просмотр')
            ->whereNumber('id')
            ->name('show');

        Route::post('/', [YourController::class, 'create'])
            ->setLabel('Создание')
            ->name('store');

        Route::put('/{id}', [YourController::class, 'update'])
            ->setLabel('Обновление')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}', [YourController::class, 'delete'])
            ->setLabel('Удаление')
            ->whereNumber('id')
            ->name('destroy');
    });
```

**Naming convention маршрутов:** `{channel}.{domain}.{module}.{action}`

## Responses

Response-классы наследуют `JsonResponse` (обёртка над Laravel `JsonResource`). Сами классы пустые — содержат только Swagger-аннотации. Данные передаются через `make()`.

### YourResponse — одиночный объект

```php
namespace App\ParkingApp\Entrypoints\Admin\YourDomain\YourModule\Http\Responses;

use MPS\Core\Http\Responses\JsonResponse;

/**
 * @OA\Schema(
 *   @OA\Property(property="id",     type="integer"),
 *   @OA\Property(property="name",   type="string"),
 *   @OA\Property(property="status", ref="#/components/schemas/YourStatusEnum"),
 * )
 */
class YourResponse extends JsonResponse
{
}
```

### YourPaginateResponse — список с пагинацией

```php
/**
 * @OA\Schema(
 *   @OA\Property(
 *     property="items",
 *     type="array",
 *     @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/YourResponse")}),
 *   ),
 *   @OA\Property(
 *     property="pagination",
 *     type="object",
 *     allOf={@OA\Schema(ref="#/components/schemas/PaginateResponse")},
 *   ),
 * )
 */
class YourPaginateResponse extends JsonResponse
{
}
```

### Использование в контроллере

```php
// Одиночный объект
return response()->json(YourResponse::make($result));

// Список
return response()->json(YourPaginateResponse::make($result));

// Созданный ресурс
return response()->json(YourResponse::make($result), 201);
```

`$result` — массив, который возвращает `service->searchById()` или `service->search()`. Response-класс передаёт данные как есть, без маппинга.

---

## Entrypoint ServiceProvider

```php
namespace App\ParkingApp\Entrypoints\Admin\YourDomain\YourModule;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'admin:your-domain:your-module';
    }
}
```

Зарегистрировать в родительском ServiceProvider канала.
