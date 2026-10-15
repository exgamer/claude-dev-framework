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

Применяй все правила из загруженных файлов (`architecture/*.md`, `structure.md`, `conventions.md`, `style.md`, `review-findings.md` и SDK документации).

Отдельно для платёжных путей (`workflows/**/payment`, `entrypoints/**/{kaspi,halyk,kassa24,...}`) — каждый пункт `approaches/patterns/payments.md` проверяй явно и пиши в отчёт результат, даже если нарушений нет; Go-кейсы, которые уже стреляли, — `review-findings.md`, «Платёжные хендлеры».

## 5.3 — Проверки за пределами дифа

Выполняй при ревью дифа/MR; при `--all` — только пп. 0, 0a, 0b по всему проекту:

0. **Запросы к хранилищам вне `internal/infrastructure`** — всегда, механически (`conventions.md` п. 4; тег `service-direct-db-query`):
   ```
   grep -rnE "gorm\.|sqlx?\.|pgx\.|redis\.(Client|UniversalClient)|\.QueryRow|\.Table\(|\.Model\(" \
     --include='*.go' . | grep -vE "/infrastructure/|/bootstrap/|_test\.go|/vendor/"
   ```
   Строка в `domains/**`, `workflows/**`, `entrypoints/**` — **[ОШИБКА]** `service-direct-db-query`: запрос переносится в репозиторий, слой получает метод интерфейса. Исключения: `bootstrap/` (DI — клиент создаётся и раздаётся репозиториям, отфильтрован), `entrypoints/system/healthcheck` (пингует БД напрямую — так в эталоне). `.Exec(`/`.Raw(` в паттерне нет намеренно: так называются методы workflow и `httpresponse` в SDK. Проверено на `parking-session-service` 2026-10-14: кроме healthcheck срабатываний нет.
0a. **Повторённая обработка формата** (`duplicated-format-logic`, `core/principles.md`, «Повторяющаяся обработка — один хелпер»):
   ```
   grep -rnE "unicode\.IsDigit|strings\.Map\(|regexp\.MustCompile\(\"\[\^0-9\]|\"\+7\"|TrimPrefix\(.*\"(\+7|8|7)\"" \
     --include='*.go' . | grep -vE "/vendor/|^\./pkg/|_test\.go"
   ```
   Строка в `domains/`, `workflows/`, `entrypoints/` — кандидат: есть `validation.NormalizePhoneNumber`/`CheckValidPhone` в SDK или пакет в `pkg/` — **[ОШИБКА]**, второе вхождение без пакета — **[ВНИМАНИЕ]**, одно — **[ИНФО]** с предложением вынести в `pkg/`.
0b. **Копии enum, импорт чужого домена, логика в Common** (`duplicated-enum`, `common-with-logic`; `architecture/layers.md`, «Общий бизнес-словарь»):
   ```
   # пары enum с одним набором значений
   python3 - <<'PY'
   import re,glob
   e={f:set(re.findall(r'=\s*"([^"]+)"',open(f).read())) for f in glob.glob('internal/**/*_enum.go',recursive=True)}
   e={f:v for f,v in e.items() if v}; fs=list(e)
   for i,a in enumerate(fs):
       for b in fs[i+1:]:
           c=e[a]&e[b]
           if len(c)>=3 and len(c)/min(len(e[a]),len(e[b]))>=0.6: print(len(c),a,b)
   PY
   # импорты доменов из доменов — сверить {domain} в пути импорта с папкой файла
   grep -rnE '"[^"]+/internal/domains/[^"/]+' internal/domains --include='*.go' | grep -v '_test\.go'
   # зависимости Common
   grep -rnE '"[^"]+/internal/(domains|infrastructure|workflows|entrypoints|app)/|"context"|"net/http"|"database/sql"|gorm' internal/common --include='*.go'
   ```
   Пара enum, где один файл — в дифе, и это то же понятие — **[ОШИБКА]** `duplicated-enum` (в находке — перенос в `internal/common/` и откуда импортировать); совпадение случайных значений разных понятий — не находка. Импорт другого `{domain}` из `internal/domains/{domain}/` — **[ОШИБКА]**: enum или value-объект — `duplicated-enum` с переносом в `internal/common/`, сервис или репозиторий — сценарий двух доменов, его место — workflow (O-7). Строка третьей команды — **[ОШИБКА]** `common-with-logic`.
1. **Разъезд списков, новый статус без потребителя, данные под миграцию, гигиена MR** — правила общие, `approaches/process/review.md`, «За пределами дифа»; здесь только что искать в Go: `switch`/`map` по типу статуса, `enums:` в swagger-аннотациях DTO, строковые литералы значений; `go.sum` вместе с `go.mod`; локальной базы в репозитории может не быть — тогда проверку колонок явно поручать автору.
2. **Инструменты.** `go build ./... && go vet ./... && go test ./... && gofmt -l internal pkg`; с горутинами — `go test -race`. CI проекта часто только containerize + deploy — локальный прогон единственный гейт, результат в отчёт явно. Падение e2e из `tests/*` без `APP_URL` («unsupported protocol scheme») — не сигнал; локальный гейт — юнит-тесты пакетов. Swagger: `swag init -g main.go -o docs --parseDependency --parseInternal --parseDepth 1`.
3. **Платёжные пути** — `approaches/patterns/payments.md`, каждый пункт явно; новый платёжный сценарий без теста в `tests/<шлюз>/` — законное замечание.
