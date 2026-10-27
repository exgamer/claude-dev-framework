# Теги находок (Go): дополнение к общему каталогу

Общий каталог и правила уровней — `approaches/process/review-tags.md` (уровень находки — только в каталогах, O-24); теги безопасности — `approaches/process/security.md`, «Каталог тегов безопасности». Здесь — теги, у которых механизм есть только в Go.

| Тег | Уровень | Суть | Источник правила |
|---|---|---|---|
| `interface-file-group` | [ВНИМАНИЕ] | Интерфейс-зависимость не в своём файле: в модуле домена или workflow — не в файле своей группы (`repository.go`, `tx_manager.go`, `modules.go`, `system.go`), в файле сервиса или по имени одного интерфейса; в остальных пакетах — не в файле по имени интерфейса (инлайном в файле адаптера, хендлера); файл с общим термином в имени (`ports.go`…) — `interface-file-generic-name` | `conventions.md` п. 12 |
| `interface-file-generic-name` | [ОШИБКА] | Файл интерфейсов назван общим hexagonal-термином (`ports.go`, `interfaces.go`, `dependencies.go`) — не отражает, что объявлено; в модуле домена и workflow — вместо файла группы | `conventions.md` п. 12, 13 |
| `file-named-by-abstraction` | [ВНИМАНИЕ] | Файл общих объявлений назван по абстракции (`types.go`, `const.go`, `constants.go`, `consts.go`, `utils.go`, `helpers.go`, `common.go`, `misc.go`), а не по смыслу (`enum.go`, `dto.go`, `entity.go`) | `conventions.md` п. 13; `core/style.md`, «Имена» |
| `constants-in-service-file` | [ВНИМАНИЕ] | Константа пакетного уровня в файле Service/Command/Query модуля домена (`service.go`, `*_service.go`, `*_command.go`, `*_query.go`) вместо `enum.go` модуля | `conventions.md` п. 13 |
| `state-field-exported` | [ОШИБКА] | У новой сущности (не только для чтения, `regulations/entity.md`, «Когда применять») поле состояния (статус, флаг состояния) экспортируемое — его можно записать мимо методов перехода; нужно неэкспортируемое поле, геттер, методы перехода и `Restore*` | `regulations/entity.md`, «Переход состояния» (O-28) |
| `nil-dependency-injected` | [ОШИБКА] | В конструктор тихо передан `nil` вместо зависимости, вместо явной опциональности (nil-check с деградацией) или разделения сервиса | `conventions.md` п. 21 |
| `mapper-missing-nil-check` | [ОШИБКА] | Маппер с входом-указателем обращается к полям без проверки на `nil` (выход-указатель — `nil`, выход-коллекция — пустая коллекция, выход-значение — нулевое значение); Request в маппер точки входа — по значению, проверка не нужна | `go-sdk/infra.md`, «Правила» |
| `sdk-not-in-design` | [ВНИМАНИЕ] | Код на SDK, а источник и версия SDK не записаны в `design.md` (раздел «SDK») | `conventions.md` п. 23 |
| `integration-test-without-build-tag` | [ВНИМАНИЕ] | Интеграционный тест без build tag — `go test ./...` падает без поднятой инфраструктуры | `structure.md`, «Тесты» |
| `tx-bypassed-by-service` | [ОШИБКА] | Внутри `fn` транзакции SDK — сервис, Command домена или репозиторий из поля структуры: они на пуле, запись уходит мимо транзакции | `approaches/patterns/transactions.md`, «Общее»; `go-sdk/infra/postgres.md`, «Транзакции» |
| `paginator-timeout-units` | [ОШИБКА] | `NewGormPaginatedHelper.SetTimeout` с `time.Duration` (`10*time.Second`) вместо числа секунд: хелпер умножает на `time.Second` сам, int64 переполняется, список всегда 500 | `go-sdk/infra/postgres.md`, «Пагинация» |
| `validate-tag-ignored` | [ОШИБКА] | Поле запроса с тегом `validate:"…"` без `binding:"…"` или с другими правилами: валидатор gin в SDK читает только `binding` — правила из `validate` молча не проверяются (границы, `required`) | `conventions.md` п. 7; `go-sdk/entrypoint/http.md` |
| `sdk-reimplemented` | [ОШИБКА] | Своя реализация (хелпер, обёртка, клиент, middleware) того, что есть в SDK | `go-sdk/capabilities.md`; `core/principles.md`, «Инфраструктурный код — один раз» |
| `response-middleware-missing` | [ОШИБКА] | Группа маршрутов, хендлеры которой вызывают `response.*`, без `FormattedResponseMiddleware` — ответ пустой | `go-sdk/entrypoint/http.md` |
| `rabbit-own-connection` | [ОШИБКА] | Своё `amqp.Dial`-соединение в обход kernel-а (для очереди без читателя и т.п.) | `go-sdk/entrypoint/rabbit.md` |
| `rabbit-adapter-same-name` | [ВНИМАНИЕ] | Инфра-адаптер над `*rabbitmq.Publisher` назван `Publisher`, как доменный интерфейс и тип SDK | `go-sdk/entrypoint/rabbit.md` |
| `rabbit-wrapper-over-adapter` | [ВНИМАНИЕ] | Второй слой обёртки над адаптером публикации без новой логики | `go-sdk/entrypoint/rabbit.md` |
| `rabbit-topology-in-infrastructure` | [ВНИМАНИЕ] | Декларация топологии без своего consumer-а лежит в `internal/infrastructure/rabbit/…`, а не в Bootstrap рядом с вызывающим | `go-sdk/entrypoint/rabbit.md` |
| `gorm-tablename-missing` | [ИНФО] | У GORM-модели нет `TableName()` — GORM выводит имя сам; не нарушение, уточнить имя таблицы | `go-sdk/infra/postgres.md`, «Пагинация» |
| `http-status-unchecked` | [ОШИБКА] | `resp.Result` используется без проверки статуса ответа (`IsSuccess`/`IsClientError`/`IsServerError`) | `go-sdk/infra/http.md` |
| `data-race` | [ОШИБКА] | Общая изменяемая память между горутинами без `sync.Mutex`/`atomic`/владения одной горутиной; гонка, найденная `-race` | `approaches/patterns/concurrency.md`, «Data race — Go» |
| `goroutine-loop-capture` | [ОШИБКА] | Горутина захватывает изменяемую переменную цикла вместо параметра | `approaches/patterns/concurrency.md`, «Data race — Go» |
| `shared-mutable-state` | [ОШИБКА] | Изменяемое состояние после конструктора в структурах, разделяемых между запросами | `approaches/patterns/concurrency.md`, «Data race — Go» |
| `unbounded-goroutines` | [ОШИБКА] | Горутина без ожидания, остановки по `ctx`, обработки ошибки и паники; `go` в цикле по входным данным без ограничения параллелизма | `approaches/patterns/concurrency.md`, «Data race — Go»; `performance.md`, «Go» |
| `channel-misuse` | [ОШИБКА] | Канал закрывает не отправитель или дважды, отправка в закрытый, вечная блокировка без `ctx.Done()` | `approaches/patterns/concurrency.md`, «Data race — Go» |
| `context-not-propagated` | [ОШИБКА] | `ctx` не первым параметром в методе с I/O (или вызывающем его); `context.Background()`/`TODO()` в бизнес-коде; ctx в поле структуры (кроме сессии WebSocket) | `checklist.md` п. 18, `conventions.md` п. 1, 2; `structure.md`, «Entrypoint Layer» |
| `imports-not-grouped` | [ОШИБКА] | Файл не по `gofmt` или импорты не тремя группами (std / внешние / свои модуля) | `checklist.md` п. 33, `style.md` |
| `initialism-case` | [ВНИМАНИЕ] | Аббревиатура не заглавными в имени (`Id`, `Url`, `Dto` вместо `ID`, `URL`, `DTO`) | `checklist.md` п. 34, `style.md` |
| `missing-blank-before-return` | [ВНИМАНИЕ] | Нет пустой строки перед `return`/`continue`/`break`, кроме первой строки блока | `checklist.md` п. 35, `style.md` |
