# Ревью Go: проверки сверх правил

Из `go-reviewer` (разделы 5.0, 5.2, 5.3). Общая процедура ревью — `approaches/process/review.md`; безопасность — `security.md`.

> Таблица путей ниже — из `go-reviewer`; при расхождении главнее `structure.md` (решения G-2, G-4: инфра внешних систем — `http/{provider}/{product}`, служебные — `entrypoints/system/http/…`).

## 5.0 — Проверка расположения файла

Перед проверкой кода определи тип компонента по имени файла и содержимому, затем сверь фактический путь с ожидаемым. Нарушение — **[ОШИБКА]**.

| Тип компонента | Признаки определения | Ожидаемый путь |
|---|---|---|
| Domain Entity | `entity.go`, plain struct без ORM/JSON тегов | `internal/domains/{domain}/{module}/` |
| Domain Repository | `repository.go`, объявляет interface | `internal/domains/{domain}/{module}/` |
| Domain Service | `service.go`, методы через repository interface | `internal/domains/{domain}/{module}/` |
| Infra Repository (Postgres) | `repository.go`, импортирует `gorm` или `gosdk-postgres-core` | `internal/infrastructure/postgres/{domain}/{module}/` |
| Infra Repository (Redis) | `repository.go`, импортирует `gosdk-redis-core` | `internal/infrastructure/redis/{domain}/{module}/` |
| Infra Repository (HTTP) | `repository.go`, импортирует `gosdk-http-request-builder` | `internal/infrastructure/http/{provider}/{product}/` — внешний провайдер; `internal/infrastructure/http/{service}/{module}/` — наш сервис (G-2) |
| Infra Model | `model.go`, struct с `gorm:` тегами | `internal/infrastructure/postgres/{domain}/{module}/` |
| HTTP Handler | `handler.go`, методы принимают `*gin.Context` | `internal/entrypoints/{context}/http/{domain}/{module}/` |
| RabbitMQ Consumer | `{module}_consumer.go`, метод `Consume(ctx, *message.Message)` | `internal/entrypoints/{context}/rabbit/{domain}/{module}/` или `internal/entrypoints/{context}/rabbit/{module}/` |
| Workflow | любой `.go` с оркестрацией нескольких доменных сервисов | `internal/workflows/{domain}/{module}/` или `internal/workflows/{module}/` |
| Bootstrap Module | `module.go` + `repositories_factory.go` / `services_factory.go` / `handlers_factory.go` | `internal/app/bootstrap/{module}/` или `internal/app/bootstrap/{domain}/{module}/` |

**Правило проверки:**
1. Определи тип компонента (один или несколько из таблицы выше)
2. Возьми фактический путь файла
3. Сверь с ожидаемым паттерном: путь должен содержать все обязательные сегменты
4. Если путь не соответствует — **[ОШИБКА]** с указанием ожидаемого паттерна
5. Если тип компонента не удаётся определить однозначно — пропусти проверку пути для этого файла

## Платёжные пути

Применяй все правила из загруженных файлов (`architecture.md`, `conventions.md`, `style.md`, `review-findings.md` и SDK документации).

Отдельно для платёжных путей (`workflows/**/payment`, `entrypoints/**/{kaspi,halyk,kassa24,...}`) — каждый пункт раздела «Платёжные хендлеры» из `review-findings.md` проверяй явно и пиши в отчёт результат, даже если нарушений нет: это повтор прод-инцидентов, а не стиль.

## 5.3 — Проверки за пределами дифа

Выполняй при ревью дифа/MR (не при `--all`):

1. **Разъезд `const`-блоков и статусов.** Новое значение типа-статуса → `grep` по `switch`/`map` по этому типу, по `enums:` в swagger-аннотациях DTO, по строковым литералам. Рассинхрон — **[ОШИБКА]** с перечислением мест.
2. **Новый статус без потребителя.** Появился `Status*` — покажи, кто его обрабатывает (cron, consumer, переход в workflow). Нет потребителя — **[ОШИБКА]** (тупик навсегда, см. `review-findings.md`).
3. **Данные под миграцию поведения.** Новый `NOT NULL`, новое значение enum, новый FK — что лежит в колонке на stage/prod. Если локальной базы в репо нет — явно поручить проверку автору, а не пропускать пункт.
4. **Инструменты.** `go build ./... && go vet ./... && go test ./... && gofmt -l internal pkg`. Если CI проекта не гоняет lint/test (только containerize + deploy) — локальный прогон единственный гейт, его результат писать в отчёт явно. Падение e2e из `tests/*` без `APP_URL` («unsupported protocol scheme») — не сигнал; локальный гейт — юнит-тесты пакетов.
5. **Гигиена MR** (**[ВНИМАНИЕ]**, деплойный риск — **[ОШИБКА]**):
   - `go.sum` обновлён вместе с `go.mod`; нет неиспользуемых импортов и конфликт-маркеров;
   - менялись роуты/DTO → swagger в `docs/` перегенерирован (`swag init -g main.go -o docs --parseDependency --parseInternal --parseDepth 1`);
   - новый HTTP-роут без auth-middleware — вопрос «осознанно?» с фиксацией ответа в MR;
   - название MR = тикет + суть, коммиты `feat|perf|conf|fix|refactor: описание #номер`;
   - включённый флаг / раскомментированный планировщик — деплойный риск, отдельный MR;
   - новый платёжный сценарий без теста в `tests/<шлюз>/` — законное замечание.
