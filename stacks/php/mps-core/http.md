# HTTP слой (Entrypoints)

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Правила точки входа — `../conventions.md` п. 1, 9, 11a, 12, 12c–12d, 18–20; здесь — как они выглядят на классах ядра и подпроекта. Эталон — `../examples/parking_app/Entrypoints/Admin/Billing/`.

## Структура

Раскладка — `../structure.md`: `Entrypoints/{Context}/{Domain}/{Module}/Http/{Controllers,Requests/{Entity},Responses}/`, `routes.php` и `ServiceProvider.php` — на уровне `Entrypoints/{Context}/{Domain}/`. Имена Request — `Requests/{Entity}/{Create,Update,Index}Request`, для действия над записью по id пути — `{View,{Action}}Request` (P-5, `../style.md`, «Имена»).

## Controller

Тонкий: Request → DTO → сервис/workflow → `response()->json(new {E}Response(...))`. Запросов к БД, ветвлений и сборки массивов нет (п. 1). Перевод результата в HTTP — здесь: сервис вернул `null` → `NotFoundAppException` (`exceptions.md`, «Где бросать»).

Базовый класс — контроллер приложения Laravel `App\Http\Controllers\Controller`, своего в `Core/` нет (`../structure.md`, «Контроллер»).

```php
use App\Http\Controllers\Controller;
use App\ParkingApp\Core\Http\RequestHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        // Список в форме (2) — явный метод репозитория paginate…({E}FilterDto, Page) (форма — по «Список» design.md, P-19, п. 2c); контекст из middleware — полем DTO (п. 19a; не выставлен — исключение в геттере DTO, null — только явно)
        $filter = TariffFilterDto::make()
            ->fromArray($request->validated())
            ->setParkingIds(RequestHelper::getParkingIds($request));

        $tariffs = $this->service->paginate($filter, Page::fromRequest($request));

        return response()->json(new TariffPaginatedResponse($tariffs));
    }

    #[OA\Get(path: '/parking/admin/tariffs/{id}', summary: 'Тариф по ID', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function view(int $id, Request $request): JsonResponse
    {
        $tariff = $this->service->findById($id, RequestHelper::getParkingIds($request));
        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return response()->json(new TariffResponse($tariff));
    }

    #[OA\Post(path: '/parking/admin/tariffs', summary: 'Создание тарифа', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function create(CreateRequest $request): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());   // DTO по всей цепочке (P-18, п. 11a)

        $tariff = $this->createTariffWorkflow->execute($dto);

        return response()->json(new TariffResponse($tariff), Response::HTTP_CREATED);
    }

    #[OA\Put(path: '/parking/admin/tariffs/{id}', summary: 'Обновление тарифа', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function update(int $id, UpdateRequest $request): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());   // PUT — полные данные (O-10, п. 12)

        $tariff = $this->service->update($id, RequestHelper::getParkingIds($request), $dto);   // скоуп — аргументом (п. 19a)

        return response()->json(new TariffResponse($tariff));
    }

    #[OA\Delete(path: '/parking/admin/tariffs/{id}', summary: 'Удаление тарифа', tags: ['Admin Parking: Тарифы'], /* … */)]
    public function delete(int $id, Request $request): JsonResponse
    {
        $this->service->deleteById($id, RequestHelper::getParkingIds($request));   // скоуп — аргументом (п. 19a)

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
```

Обёртку `success/data` даёт `ApiResponseMiddleware` на маршруте — контроллер и Response её не собирают (п. 19, тег `response-double-wrap`); макросы `Response::ok()` — нет (`controller-response-macro`).

## Requests

Базовый класс — подпроекта: `Core/Requests/Request` (наследует `MPS\Core\Http\Requests\FormRequest`); от него наследуются все Request модуля, включая `IndexRequest` списка в форме (2) (P-19, эталон — `examples/parking_app/.../Requests/Tariffs/IndexRequest.php`). В форме (1) `IndexRequest` наследует `Core/Requests/SearchRequest` (над `MPS\Core\Http\Requests\SearchRequest`: `page`, `per_page`, `sort` по `$sortAttributes`) — `repository.md`, «Форма (1)». `CommandRequest`/`ByIdsSearchRequest`/`StatusChangeRequest` ядра — для готовых компонентов (`StatusChangeServiceTrait`), в обычном модуле не нужны.

Только формат (п. 9): `gt:0` для id, `max:` для строк и массивов, `Rule::enum()`, вложенная коллекция — `list` + `{поле}.*: array`. Без SQL (`exists:`/`unique:` — P-17), без проверки доступа (middleware, п. 19a). Нормализация входа (телефон) — `prepareForValidation()` вызовом хелпера (`core/principles.md`, «Повторяющаяся обработка — один хелпер»).

```php
use App\ParkingApp\Core\Requests\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TariffCreateRequest', required: ['parking_id', 'name'], properties: [
    new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
    new OA\Property(property: 'name', description: 'Название', type: 'string', example: 'Стандарт'),
    new OA\Property(property: 'currency', ref: '#/components/schemas/CurrencyEnum', description: 'Валюта, по умолчанию KZT'),   // enum ядра — схема в Core/OpenApi (swagger.md)
    new OA\Property(property: 'grace_minutes', description: 'Льготный период (минуты)', type: 'integer', example: 15, nullable: true),
], type: 'object')]
class CreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'parking_id' => ['required', 'integer', 'gt:0', 'max:' . self::ID_MAX],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', Rule::enum(CurrencyEnum::class)],   // не прислано — умолчание DTO; явный null — 422
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:' . TimeUnitEnum::DAY->minutes()],          // очищаемое
        ];
    }
}

// UpdateRequest (PUT): каждое поле required, очищаемое — present|nullable (п. 12)
'currency' => ['required', 'string', Rule::enum(CurrencyEnum::class)],
'grace_minutes' => ['present', 'nullable', 'integer', 'min:0', 'max:' . TimeUnitEnum::DAY->minutes()],

// IndexRequest: каждое поле → поле {E}FilterDto → условие в paginate… (п. 12d); сортировка — enum (п. 12c)
class IndexRequest extends Request
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:0', 'max:' . Page::MAX_PAGE],              // 0 — первая страница (regulations/rest-api.md); верх — глубокий OFFSET
            'per_page' => ['sometimes', 'integer', 'between:1,' . Page::MAX_PER_PAGE],
            'parking_id' => ['nullable', 'integer', 'gt:0', 'max:' . self::ID_MAX],        // предел bigint: больше — 422, не 500
            'status' => ['nullable', 'string', Rule::enum(TariffStatusEnum::class)],
            'sort' => ['sometimes', 'string', Rule::enum(TariffSortEnum::class)],
        ];
    }
}
```

Query-параметры `IndexRequest` описываются `#[OA\Parameter]` (на классе Request с `parameter:`-именами, ссылки из контроллера — `ref: '#/components/parameters/…'`, как в эталоне).

## Routes

`Entrypoints/{Context}/{Domain}/routes.php`, подхватывается `ServiceProvider` контекста (п. 20):

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['api.response', 'auth:admin-api', 'resolve.admin.parkings', 'permission:parking.admin.tariffs'])   // api.response — первым: иначе 401/403 middleware без обёртки (п. 20); алиасы из bootstrap/app.php, не ::class; право — на модуль (`architecture/layers.md`); раздельные права на чтение/запись — по маршруту, если нужно (как в эталоне)
    ->prefix('parking/admin')                                                        // {подпроект}/{context}
    ->as('parking.admin.')                                                           // без whereNumber('id'): id пути — 422 в Request (п. 20)
    ->group(function () {
        Route::get('tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('tariffs/{id}', [TariffController::class, 'view'])->name('tariffs.view');
        Route::post('tariffs', [TariffController::class, 'create'])->name('tariffs.create');
        Route::put('tariffs/{id}', [TariffController::class, 'update'])->name('tariffs.update');
        Route::delete('tariffs/{id}', [TariffController::class, 'delete'])->name('tariffs.delete');
        Route::post('tariffs/{id}/set-default', [TariffController::class, 'setDefault'])->name('tariffs.set-default');
    });
```

Имена — `{ресурсы}.{index|view|create|update|delete}` (методы контроллера называются так же), действие над ресурсом — `{ресурсы}.{действие}` в kebab-case (`tariffs.set-default`); URL — kebab-case, множественное число; действие вне CRUD — последний сегмент с `POST` (`regulations/rest-api.md`). Роль/право — middleware на маршруте, в контроллере проверок нет (п. 19a).

## Responses

`{E}Response` и `{E}PaginatedResponse` — `JsonResource` (`Illuminate\Http\Resources\Json\JsonResource`), на классе — `#[OA\Schema]` с полями ответа; в контроллере — `new TariffResponse($model)`. Форма (2) списка: пагинация (`items`, `pagination`) — одним классом подпроекта в `Core/Resources` (P-22), `{E}PaginatedResponse` его наследует. Данные отдаются как есть, без `success/data`.

```php
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'TariffResponse', properties: [
    new OA\Property(property: 'id', description: 'ID тарифа', type: 'integer', example: 1),
    new OA\Property(property: 'name', description: 'Название', type: 'string', example: 'Стандарт'),
    new OA\Property(property: 'status', ref: '#/components/schemas/TariffStatusEnum', description: 'Статус'),
], type: 'object')]
class TariffResponse extends JsonResource
{
}
```

Форма (1) списка — `PaginateResource::make($service->search($search))` над `SearchDataObject` (`repository.md`, «Форма (1)»). Какую писать — раздел «Список» `design.md` (P-19, `../conventions.md` п. 2c).

## Entrypoint ServiceProvider

```php
namespace App\ParkingApp\Entrypoints\Admin\Billing;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

/**
 * Подключает routes.php админки Billing автозагрузкой ModuleServiceProvider; middleware и права — в самих маршрутах.
 *
 * @author …
 */
class ServiceProvider extends CoreServiceProvider
{
}
```

Пустой, как в эталоне: `getName()` ядра не переопределяется (`domain.md`).

Регистрируется в корневом `ServiceProvider.php` подпроекта (`../structure.md`, чеклист модуля).
