# Swagger / OpenAPI

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Где что живёт

| Что | Где                                   |
|---|---------------------------------------|
| `@OA\Info`, глобальные параметры и схемы | `app/Http/Controllers/Controller.php` |
| `@OA\Get/Post/Put/Delete` на методах | Controller в `Entrypoints/{channel}/` |
| `@OA\Schema` полей запроса | Request класс                         |
| `@OA\Schema` полей ответа | Response класс                        |

---

## Controller — метод

```php
/**
 * @OA\Put(
 *     operationId="basketUpsert",
 *     path="/api/cart/basket",
 *     tags={"cart:basket:client"},
 *     summary="Добавление",
 *     description="Добавление",
 *     @OA\Parameter(ref="#/components/parameters/app_auth"),
 *     @OA\Parameter(ref="#/components/parameters/app_device_id"),
 *     @OA\Parameter(ref="#/components/parameters/app_locale"),
 *     @OA\RequestBody(
 *       required=true,
 *       @OA\JsonContent(ref="#/components/schemas/BasketUpsertRequest"),
 *     ),
 *     @OA\Response(
 *       response=201,
 *       description="Ok",
 *       @OA\JsonContent(ref="#/components/schemas/BasketResponse"),
 *     ),
 *     @OA\Response(response=422, ref="#/components/responses/422"),
 * )
 */
public function upsert(BasketUpsertRequest $request): JsonResponse { ... }
```

**Формат тегов:** `{domain}:{module}:{channel}` — например `cart:basket:client`, `order:order:admin`

**Готовые параметры (ref):**
- `app_auth` — заголовок Authorization
- `app_locale` — заголовок Accept-Language
- `app_device_id` — заголовок Device-Id
- `app_api_key` — заголовок Api-Key
- `search_page`, `search_per_page`, `search_sort` — query-параметры пагинации

**Готовые ответы (ref):**
- `#/components/responses/422` — ошибка валидации
- `#/components/responses/204` — пустой ответ

---

## Request — схема

```php
/**
 * @OA\Schema(
 *    required={"products"},
 *    @OA\Property(
 *      property="products",
 *      title="Товары",
 *      type="array",
 *      @OA\Items(
 *        required={"product_id", "quantity"},
 *        @OA\Property(property="product_id", title="ID", type="integer", example="1"),
 *        @OA\Property(property="quantity",   title="Кол-во", type="integer", example="1"),
 *        @OA\Property(
 *          property="source",
 *          ref="#/components/schemas/BasketSourceEnum",
 *          nullable=true,
 *        ),
 *      ),
 *    ),
 * )
 */
class BasketUpsertRequest extends CommandRequest { ... }
```

Схема называется по имени класса автоматически (`BasketUpsertRequest` → `#/components/schemas/BasketUpsertRequest`).

---

## Response — схема

```php
/**
 * @OA\Schema(
 *   @OA\Property(property="id",   title="ID",    type="integer"),
 *   @OA\Property(property="name", title="Имя",   type="string"),
 * )
 */
class BasketResponse extends JsonResponse {}
```

Response-класс пустой — только аннотация.

---

## Enum — схема

```php
/**
 * @OA\Schema(
 *   type="string",
 *   enum={"pending","active","archived"},
 * )
 */
enum StatusEnum: string { ... }
```

Ссылка в свойстве: `ref="#/components/schemas/StatusEnum"`.
