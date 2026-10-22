# Swagger / OpenAPI

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Правило — `../conventions.md` п. 18: атрибуты `#[OA\…]` (`use OpenApi\Attributes as OA;`), не docblock-аннотации `@OA\…`. Тексты `summary`/`description` — по-русски, по делу (`core/style.md`, «Любой текст для людей»). Эталон — `../examples/parking_app/Entrypoints/Admin/Billing/Tariffs/`.

## Где что живёт

| Что | Где |
|---|---|
| `#[OA\Info]`, общие параметры (`Authorization`, `Accept-Language`), общие ответы — `Unauthorized401`, `AccessDenied403`, `NotFound404`, `ValidationError422`, `ServerError500` (каждый — классом, как `ValidationErrorResponse`), схемы ошибок, схемы enum ядра (`CurrencyEnum`) | `Core/OpenApi/` подпроекта (`../structure.md`); общий ответ не описывается inline в контроллере и не берётся «из хоста» |
| `#[OA\Tag]` на классе, `#[OA\Get|Post|Put|Delete]` на методах | Controller в `Entrypoints/{Context}/{Domain}/{Module}/Http/Controllers/` |
| `#[OA\Schema]` полей тела запроса | Request (`Create`/`Update`) |
| `#[OA\Parameter]` query-параметров | `IndexRequest` (ссылки из контроллера — `ref: '#/components/parameters/…'`) |
| `#[OA\Schema]` полей ответа | Response |
| `#[OA\Schema]` enum | enum (`enums.md`) |

**Тег:** `'{Context} {Подпроект}: {Сущность}'` — `'Admin Parking: Тарифы'`; отдельный сервис без подпроектов — `'{Context}: {Сущность}'`. Один и тот же на `#[OA\Tag]` класса и в `tags:` каждого метода.

## Controller — метод

```php
#[OA\Post(
    path: '/parking/admin/tariffs',
    summary: 'Создание тарифа',
    security: [['Authorization' => []]],
    tags: ['Admin Parking: Тарифы'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TariffCreateRequest')),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Тариф создан',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
            ])
        ),
        new OA\Response(ref: '#/components/responses/Unauthorized401', response: 401),
        new OA\Response(ref: '#/components/responses/AccessDenied403', response: 403),
        new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
        new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
    ]
)]
public function create(CreateRequest $request): JsonResponse { … }
```

- У каждого метода — `500`; маршрут под `auth`/подписью — `401`, под `permission:`/скоупом — `403` (`regulations/rest-api.md`: все коды ответа); общие ответы — ссылкой на `Core/OpenApi`; `404` — по `../conventions.md` п. 18 (только у поиска записи по ID).
- Обёртка `success`/`data` в ответе описывается: её даёт `ApiResponseMiddleware`, и это фактический формат (`../conventions.md` п. 19); сам код обёртку не собирает.

## Request — схема

```php
#[OA\Schema(
    schema: 'TariffCreateRequest',
    required: ['parking_id', 'name'],
    properties: [
        new OA\Property(property: 'parking_id', description: 'ID парковки', type: 'integer', example: 15),
        new OA\Property(property: 'name', description: 'Название тарифа', type: 'string', example: 'Стандарт'),
        new OA\Property(property: 'status', ref: '#/components/schemas/TariffStatusEnum', nullable: true),
    ],
    type: 'object'
)]
class CreateRequest extends Request { … }
```

Query-параметры списка — на `IndexRequest`:

```php
#[OA\Parameter(parameter: 'TariffsIndexRequestStatus', name: 'status', description: 'Статус', in: 'query', schema: new OA\Schema(ref: '#/components/schemas/TariffStatusEnum'))]
#[OA\Parameter(parameter: 'TariffsIndexRequestSort', name: 'sort', description: 'Сортировка', in: 'query', schema: new OA\Schema(ref: '#/components/schemas/TariffSortEnum'))]
class IndexRequest extends Request { … }
```

Выше — форма (2) списка (P-19, `../conventions.md` п. 2c). В форме (1) (`IndexRequest extends SearchRequest`, раздел «Список» `design.md`) так же описываются `page`, `per_page` (с максимумом) и `sort` — строкой, допустимые значения из `$sortAttributes` (`name,-id`) в `description`. Ответ списка в обеих формах — `items` (схема элемента — Response модуля) + `pagination` (`ref: '#/components/schemas/PaginationMeta'`, `Core/Resources/PaginationMeta`); описывается на ответе метода контроллера внутри `data`, своей схемы у `PaginateResource` нет.

## Response — схема

```php
#[OA\Schema(schema: 'TariffResponse', properties: [
    new OA\Property(property: 'id', description: 'ID тарифа', type: 'integer', example: 1),
    new OA\Property(property: 'name', description: 'Название', type: 'string', example: 'Стандарт'),
], type: 'object')]
class TariffResponse extends JsonResource {}
```

## Enum — схема

```php
#[OA\Schema(schema: 'TariffStatusEnum', type: 'string', enum: [
    TariffStatusEnum::ACTIVE->value,
    TariffStatusEnum::SCHEDULED->value,
    TariffStatusEnum::ARCHIVED->value,
])]
enum TariffStatusEnum: string implements Enumerable { … }
```

Enum ядра (`MPS\Core\Enums\CurrencyEnum`) атрибут не получит — его схема заводится в `Core/OpenApi/` подпроекта тем же способом (`#[OA\Schema(schema: 'CurrencyEnum', type: 'string', enum: [CurrencyEnum::KZT->value, …])]` на классе-носителе), поля ссылаются на неё `ref`; `type: 'string'` с примером вместо `ref` — `enum-list-drift`.

Значения — `Enum::CASE->value` (константное выражение, PHP 8.2+), не строковые литералы: опечатка — ошибка при генерации, а не расхождение схемы. Вызов метода (`values()`) в атрибуте невозможен — кейсы перечисляются. Ссылка в свойстве: `ref: '#/components/schemas/TariffStatusEnum'`, не `enum:` литералом в Request/Response. Новый кейс enum → обновить `enum:` здесь и во всех местах, где перечислен набор (`enum-list-drift`).

## Генерация

Изменились роуты, Request, Response — Swagger перегенерирован перед MR (`regulations/rest-api.md`, `approaches/process/review.md`, п. 7 «Гигиена MR»); сгенерированный `storage/api-docs/*.json` в MR не уезжает посторонней правкой.
