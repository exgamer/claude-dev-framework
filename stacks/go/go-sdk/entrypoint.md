# Entrypoint слой

Путь:
- HTTP: `internal/entrypoints/{context}/http/{domain}/{module}/`
- RabbitMQ: `internal/entrypoints/{context}/rabbit/{domain}/{module}/`

**`{context}`** — аудитория или точка входа. Определяется тем, *для кого / откуда* предназначен эндпойнт или consumer. Примеры: `admin`, `client`, `public`, `internal`, `partner`. Каждый контекст — отдельная директория.

Примеры HTTP: `internal/entrypoints/client/http/billing/tariff/`, `internal/entrypoints/admin/http/billing/tariff/`
Примеры Rabbit: `internal/entrypoints/internal/rabbit/billing/tariff/`, `internal/entrypoints/partner/rabbit/catalog/product/`

## Правила

- Хендлер тонкий: только валидация → маппинг → вызов сервиса/workflow → ответ
- Никакой бизнес-логики и запросов к хранилищам в хендлере
- Маппинг Request → Domain и Domain → Response только через `mapper.go`
- Все эндпойнты покрыты Swagger-аннотациями

## Именование файлов

| Что | Файл |
|-----|------|
| Gin handler | `handler.go` |
| Регистрация маршрутов | `routes.go` |
| Request DTOs | `request.go` |
| Response DTOs | `response.go` |
| Маппинг DTO ↔ Domain | `mapper.go` |
| Consumer | `{module}_consumer.go` |
| Регистрация consumers | `consumer_registry.go` |
