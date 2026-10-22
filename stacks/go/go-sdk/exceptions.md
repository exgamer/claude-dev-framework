# Exceptions — обработка ошибок

Пакет: `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception` (как в `go.mod` эталона; GitHub — `github.com/exgamer/gosdk-core/pkg/exception`)

## Типы исключений

```go
exception.NewValidationException(map[string]any{"field": "msg"}, false) // → 422
exception.NewNotFoundException(errors.New("not found"), false)          // → 404
exception.NewForbiddenException(errors.New("access denied"), false)     // → 403
exception.NewAppException(err, map[string]any{"ctx": "val"}, true)      // → 500, trackInSentry=true
```

Второй параметр `bool` — отправлять ли в Sentry.

Для internal ошибок можно использовать struct-литерал напрямую:

```go
return &exception.AppException{
	Err:           err,
	Kind:          exception.ErrorKindInternal,
	TrackInSentry: true,
}
```

## Когда использовать

| Ситуация | Exception |
|----------|-----------|
| Запись не найдена | `NewNotFoundException` |
| Нет доступа | `NewForbiddenException` |
| Невалидные данные | `NewValidationException` |
| Неожиданная ошибка | `NewAppException` |

## В хендлере

Если сервис возвращает `exception.*` — используй `response.ErrorResponse`, он сам определит HTTP-статус:

```go
result, err := h.service.DoSomething(c.Request.Context(), id)
if err != nil {
	response.ErrorResponse(c, err)

	return
}
```

Явные вызовы (`response.NotFound`, `response.InternalServerError`) — только когда ошибка формируется прямо в хендлере, без сервиса.

Невалидный id пути (`0`, отрицательный, не число) — **422**, как любая ошибка валидации входа (`regulations/rest-api.md`, «Валидация входа»): ошибку `validators.GetIntQueryParam` перевести в `ValidationException`. `response.BadRequest` (400) здесь — только в существующем коде (унаследованное, уровень — `approaches/process/review-tags.md`), новый код — 422 (тег `request-missing-bounds`):

```go
id, err := validators.GetIntQueryParam(c, "id")
if err != nil {
	response.ErrorResponse(c, exception.NewValidationException(map[string]any{"id": "id должен быть положительным целым числом"}, false))

	return
}
```

## Техническая ошибка в ответе

`response.ErrorResponse` для ошибки, которая не `AppException`/`HttpException`, отдаёт 500 и пишет в `message` полный текст обёрнутой ошибки — текст драйвера Postgres/Redis уходит клиенту. Как закрыть — на выбор разработчика: обёртка на границе хендлера (`exception.NewAppException` с общим сообщением, детали — в лог/Sentry) или как есть. На ревью — тег `internal-details-leak`, уровень этого случая — каталог безопасности (`approaches/process/security.md`, сверка 2026-10-20); свой `err.Error()`/stack trace в ответе вручную — по `../security.md` как есть.

## Паника в обработчике

`InitRouter` ставит `gin.CustomRecovery(ErrorHandler)` из `http-core/pkg/gin` (http-core GitLab и GitHub, до v1.1.0 включительно): паника в обработчике — 500, текст паники в `message` и стек вызовов в `details`. Это поведение SDK, не сервиса: на ревью сервиса текст паники и стек от этого recovery — не находка `internal-details-leak`, и ввод, вызывающий панику, ради этой утечки не ищется; сама паника в коде сервиса (nil, выход за индекс, приведение без `ok`) — находка по своим пунктам памятки, как обычно; одна строка «известное поведение SDK» в отчёт. Свой recovery сервиса, отдающий текст паники или стек, — находка по `../security.md` как есть. Исправление — в SDK отдельной задачей владельца пакета (`core/principles.md`, «Инфраструктурный код — один раз»).

## Флоу

```
Service → AppException → Handler → response.ErrorResponse(c, err) → JSON
```

- **Service / Domain** — возвращает `AppException`, не знает про HTTP
- **Handler** — не анализирует тип ошибки, просто передаёт в `response.ErrorResponse`

## В сервисе / domain

```go
tariff, err := s.repository.GetByID(ctx, id, scope)
if err != nil {
	return nil, err
}

if tariff == nil {
	return nil, exception.NewNotFoundException(errors.New("тариф не найден"), false)
}
```

