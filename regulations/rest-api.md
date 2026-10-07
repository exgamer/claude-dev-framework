# REST API

Выжимка из `backend-regulation/docs/REST-API-STANDARDS.md` + `DOMAIN_NAMING_REGLAMENT.md` §7.

---

## Документация

- Каждый эндпойнт задокументирован в OpenAPI/Swagger: входные параметры, структура ответа, все HTTP-коды, ошибки, краткое описание бизнес-логики.
- Эндпойнт без документации не идёт на ревью и в релиз. Изменились роуты/DTO → swagger перегенерирован.

## Методы

| Метод | Назначение |
|---|---|
| GET | ресурс или коллекция |
| POST | создание или доменное действие, не укладывающееся в CRUD |
| PUT | полное обновление |
| PATCH | частичное обновление |
| DELETE | удаление |

URL описывает ресурс, без глаголов. Действие вне CRUD — последний сегмент `action` (`sort`, `publish`, `cancel`) с методом POST.

## URL

```
/api/<context>/<version?>/<domain>/<module>/<entity>/<action?>
```

- `context` — аудитория: `admin`, `client` (совпадает с `{context}` в `entrypoints/{context}/`).
- `version` — `v1` для всего нового.
- kebab-case, сущности во множественном числе для коллекций.

```
GET    /api/admin/v1/catalog/categories
GET    /api/admin/v1/catalog/categories/{id}
POST   /api/admin/v1/catalog/categories
PUT    /api/admin/v1/catalog/categories/{id}
PATCH  /api/admin/v1/catalog/categories/{id}
DELETE /api/admin/v1/catalog/categories/{id}
POST   /api/admin/v1/catalog/category/tree/sort
```

Версия — только в пути, никогда в домене (`api-v2.domain.kz` запрещено).

## Статусы

200 успех · 201 создано · 204 без содержимого · 400 некорректный запрос · 401 не авторизован · 403 нет прав · 404 не найдено · 422 валидация · 5xx сервер.

## Формат ответа

Единый для всех ответов (в Go его даёт `FormattedResponseMiddleware` + `response.*`, руками не собирать):

```json
{ "success": true, "data": { } }
```

Пагинация:

```json
{ "success": true, "data": { "items": [], "pagination": { "page": 1, "last_page": 1, "from": 1, "to": 2, "items_per_page": 30, "total_items": 2 }, "params": [] } }
```

Ошибка:

```json
{ "success": false, "data": { "status": 422, "error": "VALIDATION_ERROR", "message": "Входные данные не действительны.", "details": { "field": ["..."] } } }
```

## Версионирование

- Новая версия — только при breaking change: изменился формат запроса/ответа, удалены/переименованы поля, старые клиенты ломаются.
- Не меняется: новое поле, новый эндпойнт, внутренняя реализация, производительность, логика без изменения контракта.
- Изменился сам бизнес-процесс → новый эндпойнт с другим именем ресурса, а не новая версия старого.
