# Exceptions — обработка ошибок

Пакет: `github.com/exgamer/gosdk-core/pkg/exception`

## Типы исключений

```go
exception.NewValidationException(map[string]any{"field": "msg"}, false)  // → 422
exception.NewNotFoundException(errors.New("not found"), false)            // → 404
exception.NewForbiddenException(errors.New("access denied"), false)       // → 403
exception.NewAppException(err, map[string]any{"ctx": "val"}, true)        // → 500, trackInSentry=true
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
result, err := s.service.DoSomething(ctx, id)
if err != nil {
    response.ErrorResponse(c, err)
	
    return
}
```

Явные вызовы (`response.NotFound`, `response.InternalServerError`) — только когда ошибка формируется прямо в хендлере, без сервиса:

```go
if id == 0 {
    response.BadRequest(c, errors.New("id required"), nil)
	
    return
}
```

## Флоу

```
Service → AppException → Handler → response.ErrorResponse(c, err) → JSON
```

- **Service / Domain** — возвращает `AppException`, не знает про HTTP
- **Handler** — не анализирует тип ошибки, просто передаёт в `response.ErrorResponse`

## В сервисе / domain

```go
city, err := s.cityService.GetById(ctx, m.CityID)
if err != nil  { return nil, err }
if city == nil { return nil, exception.NewNotFoundException(errors.New("city not found"), false) }
```

