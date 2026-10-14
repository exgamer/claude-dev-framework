# HTTP слой (Entrypoints)

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Правила точки входа — `../conventions.md` п. 1, 9, 11a, 12, 12c–12d, 18–20; здесь — как они выглядят на классах ядра и подпроекта. Эталон — `../examples/parking_app/Entrypoints/Admin/Billing/`.

## Структура

Раскладка — `../structure.md`: `Entrypoints/{Context}/{Domain}/{Module}/Http/{Controllers,Requests/{Entity},Responses}/`, `routes.php` и `ServiceProvider.php` — на уровне `Entrypoints/{Context}/{Domain}/`. Имена Request — `Requests/{Entity}/{Create,Update,Index}Request` (P-5).

## Controller

Тонкий: Request → DTO → сервис/workflow → `response()->json(new {E}Response(...))`. Запросов к БД, ветвлений и сборки массивов нет (п. 1). Перевод результата в HTTP — здесь: сервис вернул `null` → `NotFoundAppException` (`exceptions.md`, «Где бросать»).

```php
use App\ParkingApp\Core\Http\RequestHelper;
use Illuminate\Http\JsonResponse;
use MPS\Core\Exceptions\NotFoundAppException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'Admin Parking: Тарифы')]
class TariffController extends Controller
{
    public function __construct(
        private readonly TariffCrudServiceInterface $service,
        private readonly CreateTariffWorkflow $createTariffWorkflow,
    ) {
    }

    #[OA\Get(path: '/parking/admin/tariffs', summary: 'Список тарифов', tags: ['Admin Parking: Тарифы'], /* parameters, responses */)]
    public function index(IndexRequest $request): JsonResponse
    {
        // Список — явный метод репозитория paginate…({E}FilterDto, Page) (P-19, п. 2a); контекст из middleware — полем DTO (п. 19a)
        $filter = TariffFilterDto::make()
            ->fromArray($request->validated())
            ->setBuildingIds(RequestHelper::getBuildingIds($request));

        return response()->json(new TariffPaginatedResponse($this->service->paginate($filter, Page::fromRequest($request))));
    }

    #[OA\Get(path: '/parking/admin/tariffs/{id}', summary: 'Тариф по ID', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function view(int $id, Request $request): JsonResponse
    {
        $tariff = $this->service->findById($id, RequestHelper::getBuildingIds($request));
        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return response()->json(new TariffResponse($tariff));
    }

    #[OA\Post(path: '/parking/admin/tariffs', summary: 'Создание тарифа', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function create(CreateRequest $request): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());   // DTO по всей цепочке (P-18, п. 11a)

        return response()->json(new TariffResponse($this->createTariffWorkflow->execute($dto)), Response::HTTP_CREATED);
    }

    #[OA\Put(path: '/parking/admin/tariffs/{id}', summary: 'Обновление тарифа', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function update(int $id, UpdateRequest $request): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());   // PUT — полные данные (O-10, п. 12)

        return response()->json(new TariffResponse($this->service->update($id, $dto)));
    }

    #[OA\Delete(path: '/parking/admin/tariffs/{id}', summary: 'Удаление тарифа', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function delete(int $id): JsonResponse
    {
        $this->service->deleteById($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
```

Обёртку `success/data` даёт `ApiResponseMiddleware` на маршруте — контроллер и Response её не собирают (п. 19, тег `response-double-wrap`); макросы `Response::ok()` — нет (`controller-response-macro`).

## Requests

Базовые классы — подпроекта: `Core/Requests/Request` (наследует `MPS\Core\Http\Requests\FormRequest`) и `Core/Requests/SearchRequest` (наследует `MPS\Core\Http\Requests\SearchRequest`). `CommandRequest`/`ByIdsSearchRequest`/`StatusChangeRequest` ядра — для готовых компонентов (`StatusChangeServiceTrait`), в обычном модуле не нужны.

Только формат (п. 9): `gt:0` для id, `max:` для строк и массивов, `Rule::enum()`, вложенная коллекция — `list` + `{поле}.*: array`. Без SQL (`exists:`/`unique:` — P-17), без проверки доступа (middleware, п. 19a). Нормализация входа (телефон) — `prepareForValidation()` вызовом хелпера (`core/principles.md`, «Повторяющаяся обработка — один хелпер»).

```php
use App\ParkingApp\Core\Requests\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TariffCreateRequest', required: ['parking_id', 'name'], properties: [
    new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
    new OA\Property(property: 'name', description: 'Название', type: 'string', example: 'Стандарт'),
    new OA\Property(property: 'currency', description: 'Валюта', type: 'string', example: 'KZT', nullable: true),
], type: 'object')]
class CreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'parking_id' => ['required', 'integer', 'gt:0'],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', Rule::enum(CurrencyEnum::class)],
        ];
    }
}

// UpdateRequest (PUT): каждое поле required, очищаемое — present|nullable (п. 12)
'currency' => ['present', 'nullable', 'string', Rule::enum(CurrencyEnum::class)],

// IndexRequest: каждое поле → поле {E}FilterDto → условие в paginate… (п. 12d); сортировка — enum (п. 12c)
class IndexRequest extends SearchRequest
{
    public function rules(): array
    {
        return [
            'page' => ['integer', 'gt:0'],
            'per_page' => ['integer', 'between:1,100'],
            'parking_id' => ['integer', 'gt:0'],
            'name' => ['string', 'max:255'],
            'status' => [Rule::enum(TariffStatusEnum::class)],
            'sort' => [Rule::enum(TariffSortEnum::class)],
        ];
    }
}
```

Query-параметры `IndexRequest` описываются `#[OA\Parameter]` (на классе Request с `parameter:`-именами, ссылки из контроллера — `ref: '#/components/parameters/…'`, как в эталоне).

## Routes

`Entrypoints/{Context}/{Domain}/routes.php`, подхватывается `ServiceProvider` контекста (п. 20):

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin-api', 'permission:parking.tariffs', 'superAppApi'])   // алиасы из bootstrap/app.php, не ::class
    ->prefix('parking/admin')                                                        // {подпроект}/{context}
    ->as('parking.admin.')
    ->whereNumber('id')
    ->group(function () {
        Route::get('tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('tariffs/{id}', [TariffController::class, 'view'])->name('tariffs.view');
        Route::post('tariffs', [TariffController::class, 'create'])->name('tariffs.create');
        Route::put('tariffs/{id}', [TariffController::class, 'update'])->name('tariffs.update');
        Route::delete('tariffs/{id}', [TariffController::class, 'delete'])->name('tariffs.delete');
        Route::post('tariffs/{id}/set-default', [TariffController::class, 'setDefault'])->name('tariffs.set-default');
    });
```

Имена — `{ресурсы}.{index|view|create|update|delete}` (методы контроллера называются так же); URL — kebab-case, множественное число; действие вне CRUD — последний сегмент с `POST` (`regulations/rest-api.md`). Роль/право — middleware на маршруте, в контроллере проверок нет (п. 19a).

## Responses

`{E}Response` и `{E}PaginatedResponse` — `JsonResource` (`Illuminate\Http\Resources\Json\JsonResource`), на классе — `#[OA\Schema]` с полями ответа; в контроллере — `new TariffResponse($model)`. Форма пагинации (`items`, `pagination`) — одним классом подпроекта в `Core/Resources` (P-19), `{E}PaginatedResponse` его наследует. Данные отдаются как есть, без `success/data`.

```php
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TariffResponse', properties: [
    new OA\Property(property: 'id', description: 'ID тарифа', type: 'integer', example: 1),
    new OA\Property(property: 'name', description: 'Название', type: 'string', example: 'Стандарт'),
    new OA\Property(property: 'status', description: 'Статус', type: 'string', example: 'active'),
], type: 'object')]
class TariffResponse extends JsonResource
{
}
```

Старая форма (`PaginateResource::make($service->search($search))`, `SearchDataObject`) — только в существующем коде `parking_app`/`super_app`, в новом не пишется (P-19).

## Entrypoint ServiceProvider

```php
namespace App\ParkingApp\Entrypoints\Admin\Billing;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'admin:billing';
    }
}
```

Регистрируется в корневом `ServiceProvider.php` подпроекта (`../structure.md`, чеклист модуля).
