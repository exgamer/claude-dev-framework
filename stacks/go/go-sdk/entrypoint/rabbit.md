# RabbitMQ Entrypoint

Путь (две допустимые формы):
- двухуровневый: `internal/entrypoints/{context}/rabbit/{domain}/{module}/`
- одноуровневый: `internal/entrypoints/{context}/rabbit/{module}/`

Пакет: `github.com/exgamer/gosdk-rabbit-core`

## Правила

- **Структура директорий обязана** содержать корень `internal/entrypoints/` и сегмент `rabbit/`; отсутствие любого из них — **[ОШИБКА]**:
  - неверный корень (например `entrypoint/consumer/` вместо `internal/entrypoints/{context}/rabbit/`)
  - отсутствие сегмента `rabbit/` в пути
  - плоская структура `consumer/{module}/` без `rabbit/` и `{context}/`
- Если путь корректен — отметить **[ИНФО]** какая форма используется: одноуровневая (`rabbit/{module}/`) или двухуровневая (`rabbit/{domain}/{module}/`); это не нарушение, просто фиксация выбранной структуры
- Работа с RabbitMQ только через `gosdk-rabbit-core`; прямое использование клиента запрещено
- Каждый consumer — отдельная структура со своим файлом `{module}_consumer.go`; файл с именем просто `consumer.go` (без префикса модуля) — **[ОШИБКА]**
- Регистрация consumers обязательно через `consumer_registry.go` с функцией `GetConsumers`
- `nil` = ACK, `error` = NACK (повтор); явные `msg.Ack()` / `msg.Nack()` запрещены
- Consumer тонкий — только десериализация payload и вызов сервиса/workflow; бизнес-логика в consumer запрещена
- Consumer должен быть идемпотентным — NACK вызывает повтор, логика должна это учитывать
- Ошибка десериализации payload → возвращать `nil` (ACK), не `error`; иначе сообщение будет повторяться бесконечно
- Модель payload — unexported структура внутри пакета

## Consumer

`nil` = ACK, `error` = NACK (повтор). Явные `msg.Ack()` / `msg.Nack()` не использовать.

```go
type productMessage struct {
    ID   uint   `json:"id"`
    Name string `json:"name"`
}

type Consumer struct {
    service *domain.Service
}

func NewConsumer(service *domain.Service) *Consumer {
    return &Consumer{service: service}
}

func (c *Consumer) Consume(ctx context.Context, msg *message.Message) error {
    var payload productMessage
    if err := json.Unmarshal(msg.Payload, &payload); err != nil {
        // невалидный payload — ACK чтобы не повторять бесконечно
        return nil
    }

    return c.service.Handle(ctx, payload.ID)
}
```

## consumer_registry.go

```go
func GetConsumers(consumer *Consumer) []config.HandlerRegister {
    return []config.HandlerRegister{
        {
            Handler: consumer.Consume,
            Config: config.NewConsumerTopicDurableConfig(
                "catalog-product-consumer", // уникальное имя
                "catalog-exchange",         // exchange
                "catalog-product-queue",    // очередь
                "catalog.product.*",        // routing key, wildcards поддерживаются
                10,                         // параллельных воркеров
            ),
        },
    }
}
```

## Регистрация в module.go

```go
reg, err := rabbitDi.GetRabbitConsumersRegistry(a.Container)
if err != nil {
    return err
}

reg.RegisterMultipleHandler(consumer.GetConsumers(consumersFactory.ProductConsumer))
```

## Publisher

```go
publishersReg, err := rabbitDi.GetRabbitPublishersRegistry(a.Container)
publisher := publishersReg.GetPublisher()
msg := message.NewMessage(watermill.NewUUID(), payload)
publisher.Publish("exchange-name", msg)
```

- **Тип-обёртка над `*rabbitmq.Publisher` не называется `Publisher`** — если инфра-адаптер конкретного домена (`internal/infrastructure/rabbit/{domain}/{module}/publisher.go`) реализует доменный интерфейс `Publisher` через встроенный `*rabbitmq.Publisher`, называть сам адаптер тоже `Publisher` — **[ВНИМАНИЕ]**: получается три сущности с одним именем (доменный интерфейс `Publisher`, инфра-структура `Publisher`, SDK-тип `rabbitmq.Publisher` как поле) — путаница при переходе по определению/грепе. Называть адаптер иначе (`RabbitPublisher` или с префиксом домена)
- **Обёртка ради обёртки** — если инфра-тип (`RetryScheduler`, `XxxPublisher` и т.п.) не добавляет никакой логики поверх `*rabbitmq.Publisher.Publish(...)`, кроме фиксированного routing key — это нормальный Adapter (домен не должен знать routing key). Но если поверх уже есть один такой адаптер и создаётся ещё один слой обёртки без новой логики — **[ВНИМАНИЕ]**, пересмотреть структуру

## Декларация топологии без consumer-а

Для очередей, у которых намеренно нет читателя в этом сервисе (TTL/DLX retry-очередь, dead-letter buffer и т.п.) — **не поднимать своё `amqp.Dial`-соединение**. Это отдельный TCP/AMQP-коннект в обход kernel-а, который живёт дольше, чем нужно, дублирует сборку connection string и не виден в общем графе соединений приложения — **[ОШИБКА]**.

```go
// плохо — своё соединение в обход SDK
conn, err := amqp.Dial(fmt.Sprintf("amqp://%s:%s@%s:%s%s", cfg.User, cfg.Pass, cfg.Host, cfg.Port, cfg.VHost))
defer conn.Close()
ch, err := conn.Channel()
...

// хорошо — переиспользуем соединение kernel-а (gosdk-rabbit-core >= v1.0.5)
conn, err := rabbitDi.GetRabbitConnection(a.Container)
if err != nil {
    return err
}

err = rabbitmq.DeclareTopology(conn, func(ch *amqp091.Channel) error {
    if err := ch.ExchangeDeclare(...); err != nil {
        return err
    }
    _, err := ch.QueueDeclare(...)
    return err
})
```

Такая декларация топологии (exchange/queue/binding без собственного consumer-а) — это одноразовая настройка приложения при старте, а не repository-операция; вызывающий код и сама функция декларации относятся к **Bootstrap**, а не к Infrastructure, даже если рядом лежат другие rabbit-related инфра-файлы (`publisher.go`, `retry_scheduler.go`). Расположение такого кода в `internal/infrastructure/rabbit/{domain}/{module}/` — **[ВНИМАНИЕ]**, разместить рядом с единственным вызывающим `Setup`/`module.go` в `internal/app/bootstrap/{module}/`.

## Кастомный Marshaler

Если `DefaultMarshaler` не годится (например, нужно пережить нестроковые AMQP-заголовки вроде `x-death` от Dead Letter Exchange) — **не писать свою реализацию `amqp.Marshaler` в каждом consumer-е отдельно**. С `gosdk-rabbit-core >= v1.0.5`:

```go
cfg = append(cfg, rabbitconfig.WithCustomMarshaler(marshaler.DeathTolerantMarshaler{}))
```

Если в разных consumer-ах (разных доменов) уже есть две независимые реализации, решающие одну и ту же транспортную проблему (например, обработка `x-death`) разными способами — **[ОШИБКА]**: это не доменная логика, а общая инфраструктурная проблема AMQP/watermill, которая должна решаться один раз и переиспользоваться (в SDK, если затрагивает не только этот сервис) — заменить обе копии на общую реализацию. Частный случай общего правила «не-бизнесовая функциональность не дублируется» — см. `../../conventions.md`, п. 22.
