# Outbox

Гарантированная доставка во внешнюю систему/брокер вместе с бизнес-записью.

## Когда

- После записи в БД надо отправить событие/команду наружу, и потеря недопустима (синхронизация сессий, белые списки на шлагбаумы, платежи).
- Внешняя система может быть недоступна — нужна повторная доставка.

## Форма (одинакова в обоих стеках)

1. В той же транзакции, что и бизнес-запись, создаётся строка outbox: `status` (pending/sent/failed), `operation`, `payload`, `attempts`, идентификаторы для поиска (`parking_id`, …).
2. Доставка — отдельный шаг: workflow берёт запись и шлёт наружу через репозиторий-клиент.
3. Ошибки делятся на **временные** (сеть, 5xx — повтор через очередь/ретрай) и **постоянные** (нет конфигурации, битый payload — ручной разбор, без бесконечного повтора). Тип ошибки задаёт исключение и записан в `@throws`/доке метода.
4. Повтор зависших — консольная команда / consumer по расписанию.

## Где в эталонах

- Go: `domains/session/outbox_session_event/` (сущность, статус-enum, репозиторий), `workflows/session/outbox_session_event/sync_outbox_event_command.go` (по правилам — `sync_outbox_event_workflow.go`, G-1), consumer в `bootstrap/.../consumers_factory.go`.
- PHP: `Domains/Billing/Modules/WhitelistOutbox/`, `Workflows/Billing/WhitelistOutbox/DeliverWhitelistCommand.php` (по правилам — `DeliverWhitelistWorkflow`, P-2), публикация `Infrastructure/RabbitMQ/Billing/WhitelistOutbox/`, повтор `Entrypoints/Console/Billing/WhitelistOutbox/RetryWhitelistOutboxCommand.php`.

## Нельзя

- Отправлять наружу внутри транзакции.
- Своё соединение с брокером в обход ядра (`amqp.Dial` рядом с kernel — MR!151).
- Одна outbox-таблица «на всё» с `if` по типу события — отдельная outbox на поток.
