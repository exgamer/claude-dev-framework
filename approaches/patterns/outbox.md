# Outbox

Гарантированная доставка во внешнюю систему/брокер вместе с бизнес-записью.

## Когда

- После записи в БД надо отправить событие/команду наружу, и потеря недопустима (синхронизация сессий, белые списки на шлагбаумы, платежи).
- Внешняя система может быть недоступна — нужна повторная доставка.

## Форма (одинакова в обоих стеках)

1. **Запись события** — в транзакции сценария, который делает бизнес-запись (workflow или Command/сервис домена — кто пишет, тот и пишет событие): строка outbox — `status` (pending/published/failed/awaiting_retry), тип события (routing key), поля события колонками или `payload`, `attempts`, идентификаторы для поиска (`parking_id`, …). Отдельная таблица и модуль домена на поток событий (`outbox_{поток}_event`).
2. **Доставка** — однодоменная операция модуля outbox, поэтому **Command домена** (`architecture/layers.md`, «Service, Command/Query, Workflow»), не workflow: одно событие → публикация через порт отправки (`system.go`/интерфейс публикатора) → отметка `published`; неудача → `attempts + 1`, `awaiting_retry` или `failed` по лимиту из конфига. Уже опубликованное и исчерпавшее попытки не отправляет — повтор безопасен. Первая попытка — сразу после коммита сценария; её сбой не отменяет сценарий (ошибка репортится, событие ждёт повтора).
3. Ошибки делятся на **временные** (сеть, 5xx, брокер недоступен — повтор) и **постоянные** (нет конфигурации, битый payload — `failed`, ручной разбор, без бесконечного повтора). Тип ошибки задаёт исключение и записан в `@throws`/доке метода.
   **Ручной разбор — допустимый потребитель статуса `failed`** (`approaches/process/impact-analysis.md`, «Новый статус/значение без потребителя»), если в `design.md`/MR описано, чем он выражается: выборка (`failed` по типу события, консольная команда или запрос, кто и когда смотрит) и/или алерт (событие в трекер ошибок — как в эталоне Go, `CaptureSoft` при переходе в `failed`; метрика). «Ручной разбор» без такого описания — статус без потребителя (`status-without-consumer`).
4. **Повтор зависших** — второй Command домена, его вызывает консольная команда по расписанию: пачками, выборка с `FOR UPDATE SKIP LOCKED` (`concurrency.md`), сбой одного события — репорт и дальше, ошибка выборки — стоп.

Эталон формы (Go): `stacks/go/examples/tariff/internal/domains/billing/outbox_tariff_event/` (`entity.go`, `enum.go`, `repository.go`, `system.go`, `publish_outbox_tariff_event_command.go`, `retry_outbox_tariff_events_command.go`); запись события — в транзакции `workflows/billing/tariff/create_tariff_workflow.go`; консоль — `entrypoints/system/console/billing/outbox_tariff_event/`; README эталона. Эталон формы (PHP): `stacks/php/examples/parking_app/Domains/Billing/Modules/OutboxTariffEvents/` (`Commands/{Publish,Retry}…Command.php`, `Enums/`, `Repositories/…PublisherInterface.php`), `Infrastructure/Postgres/Billing/OutboxTariffEvents/`, публикатор — `Infrastructure/RabbitMQ/Billing/OutboxTariffEvents/Publishers/OutboxTariffEventPublisher.php`, консоль — `Entrypoints/Console/Billing/OutboxTariffEvents/RetryOutboxTariffEventsConsoleCommand.php`; правила консоли и consumer — `stacks/php/mps-core/cli.md`, `stacks/php/mps-core/jobs.md`.

## Нельзя

- Отправлять наружу внутри транзакции.
- Своё соединение с брокером в обход ядра (`amqp.Dial` рядом с kernel — MR!151).
- Одна outbox-таблица «на всё» с `if` по типу события — отдельная outbox на поток.
