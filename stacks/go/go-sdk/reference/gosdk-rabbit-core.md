# git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core v1.0.6

## `git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/app`

- Константы: `RabbitKernelName`
- `type ConsumersRegistry struct`
  - `func NewConsumersRegistry() *ConsumersRegistry`
  - `func (r *ConsumersRegistry) List() []config.HandlerRegister`
  - `func (r *ConsumersRegistry) RegisterHandler(h config.HandlerRegister)`
  - `func (r *ConsumersRegistry) RegisterMultipleHandler(list []config.HandlerRegister)`
- `type PublisherRegistry struct`
  - `func NewPublisherRegistry(connection *amqp.ConnectionWrapper) *PublisherRegistry`
  - `func (r *PublisherRegistry) Close() error`
  - `func (r *PublisherRegistry) Get(name string) (*rabbitmq.Publisher, error)`
  - `func (r *PublisherRegistry) Register(def config.PublisherDefinition) error` — Register — вызывается разработчиком в модуле (без conn!)
  - `func (r *PublisherRegistry) RegisterMultiple(list []config.PublisherDefinition) error`
- `type RabbitKernel struct`
  - `func NewRabbitKernel() *RabbitKernel`
  - `func (k *RabbitKernel) EnableConsumer() *RabbitKernel`
  - `func (k *RabbitKernel) EnablePublisher() *RabbitKernel`
  - `func (k *RabbitKernel) Init(a *app.App) error`
  - `func (k *RabbitKernel) Name() string`
  - `func (k *RabbitKernel) Start(a *app.App) error`
  - `func (k *RabbitKernel) Stop(ctx context.Context) error`

## `git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/config`

- `type Config …`
  - `func NewConsumerByConfigs(bind amqp.QueueBindConfig, consumer amqp.ConsumeConfig, exchange amqp.ExchangeConfig, queue amqp.QueueConfig) []Config` — NewConsumerByConfigs - возвращает новый конфиг
  - `func NewConsumerDirectDurableConfig(consumerTag, routingKey, exchange, queue string, prefetchCnt int) []Config` — NewConsumerDirectDurableConfig - дефолтный конфиг для direct консьюмера
  - `func NewConsumerFanoutDurableConfig(consumerTag, routingKey, exchange, queue string, prefetchCnt int) []Config` — NewConsumerFanoutDurableConfig - дефолтный конфиг для fanout консьюмера
  - `func NewConsumerTopicDurableConfig(consumerTag, routingKey, exchange, queue string, prefetchCnt int) []Config` — NewConsumerTopicDurableConfig - дефолтный конфиг для topic консьюмера
  - `func NewPublisherConfig( defaultMarshaller amqp.DefaultMarshaler, publishConfig amqp.PublishConfig, exchangeConfig amqp.ExchangeConfig, topologyBuilder *amqp.DefaultTopologyBuilder, ) []Config` — NewPublisherConfig - дефолтный конфиг для publisher (без биндинга очередей!)
  - `func NewPublisherDirectDurableConfig(exchange string) []Config` — NewPublisherDirectDurableConfig Direct: topic = routing key
  - `func NewPublisherFanoutDurableConfig(exchange string) []Config` — NewPublisherFanoutDurableConfig Fanout: routing key не важен, topic можно игнорировать. Exchange фиксированный.
  - `func NewPublisherTopicDurableConfig(exchange string) []Config` — NewPublisherTopicDurableConfig Topic exchange: topic = routing key (например "orders.paid")
  - `func WithAmqpURI(uri string) Config` — WithAmqpURI - конфиг лоя лобавления ссылки коннект
  - `func WithConnectionConfig(cc amqp.ConnectionConfig) Config` — WithConnectionConfig - полная замена ConnectionConfig (TLS, raw amqp091-go Config, Reconnect)
  - `func WithConsumerConfig(cc amqp.ConsumeConfig) Config` — WithConsumerConfig - для добавление конфига консьюмера
  - `func WithCustomMarshaler(m amqp.Marshaler) Config` — WithCustomMarshaler - конфиг для маршалинга сообщений через произвольную реализацию amqp.Marshaler (в отличие от WithMarshaller, принимающего только конкретный тип amqp.DefaultMarshaler)
  - `func WithExchangeConfig(ec amqp.ExchangeConfig) Config` — WithExchangeConfig - add exchange ExchangeConfig
  - `func WithMarshaller(m amqp.DefaultMarshaler) Config` — WithMarshaller - конфиг для маршалинга сообщений
  - `func WithPublishConfig(pc amqp.PublishConfig) Config` — WithPublishConfig - add publish config PublishConfig
  - `func WithQueueName(q amqp.QueueConfig) Config` — WithQueueName - добавление конфгиа для названия очереди
  - `func WithRoutingKeyBinding(rk amqp.QueueBindConfig) Config` — WithRoutingKeyBinding - добавление конфига для бинда с routing key
  - `func WithTopologyBuilder(tp amqp.TopologyBuilder) Config` — WithTopologyBuilder
- `type ConsumeConfig struct`
- `type Handler …`
- `type HandlerRegister struct`
- `type PublisherConfig struct`
- `type PublisherDefinition struct`
  - `func NewPublisherDefinition(name string, cfg ...Config) PublisherDefinition`
- `type RabbitConfig struct`
  - `func InitRabbitConfig() (*RabbitConfig, error)` — InitRabbitConfig Инициализация конфига
  - `func (c RabbitConfig) AmqpURI() string` — AmqpURI - собирает connection string в формате amqp://user:pass@host:port/vhost.

## `git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/di`

- `func GetRabbitClient(c *di.Container) (*rabbitmq.Consumer, error)` — GetRabbitClient возвращает клиент rabbit
- `func GetRabbitConnection(c *di.Container) (*amqp.ConnectionWrapper, error)` — GetRabbitConnection возвращает общее соединение kernel-а. Нужно, когда требуется декларация топологии (exchange/queue/bind) без собственного consumer-а или publisher-а — см. rabbitmq.DeclareTopology. Не открывайте отдель…
- `func GetRabbitConsumersRegistry(c *di.Container) (*app.ConsumersRegistry, error)` — GetRabbitConsumersRegistry возвращает клиент регситр консьюмеров
- `func GetRabbitPublishersRegistry(c *di.Container) (*app.PublisherRegistry, error)` — GetRabbitPublishersRegistry возвращает клиент регситр publishers

## `git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/enums`

- Константы: `DirectExchange`
- Константы: `FanoutExchange`
- Константы: `TopicExchange`

## `git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/marshaler`

- `type DeathTolerantMarshaler struct` — DeathTolerantMarshaler оборачивает amqp.DefaultMarshaler и не роняет разбор сообщения на нестроковых AMQP-заголовках.
  - `func (m DeathTolerantMarshaler) Unmarshal(amqpMsg stdamqp.Delivery) (*message.Message, error)`

## `git.mpinnovations.kz/mps/go-packages/gosdk-rabbit-core/pkg/rabbitmq`

- `func DeclareTopology(conn *amqp.ConnectionWrapper, declare func(ch *stdamqp.Channel) error) error` — DeclareTopology открывает канал на уже существующем kernel-соединении (di.GetRabbitConnection) и выполняет в нём declare — без создания собственного consumer-а или publisher-а.
- `func NewAmqpConnection(cfg amqp.ConnectionConfig) (*amqp.ConnectionWrapper, error)`
- `type Consumer struct` — Consumer — helper для запуска нескольких подписчиков (handlers).
  - `func NewAmqpConsumer(conn *amqp.ConnectionWrapper) (*Consumer, error)`
  - `func (a *Consumer) Consume(ctx context.Context) error` — Consume — запускает все зарегистрированные handlers. Поведение: при первой ошибке подписки (Subscribe) — отменяем общий контекст и возвращаем ошибку. Обработка сообщений: handler ok => Ack; handler err/panic => Ack/Nack …
  - `func (a *Consumer) RegisterHandler(ctx context.Context, handler config.Handler, cfg ...config.Config) error` — RegisterHandler — регистрация одного консьюмера.
  - `func (a *Consumer) RegisterMultipleHandler(ctx context.Context, handlers []config.HandlerRegister) error` — RegisterMultipleHandler — регистрация набора консьюмеров.
  - `func (a *Consumer) RunConsumers(ctx context.Context, handlers []config.HandlerRegister) error` — RunConsumers — удобный метод: регистрирует и запускает Consume. ВАЖНО: передавайте ctx, чтобы можно было остановить обработку и корректно закрыть соединение.
  - `func (a *Consumer) WithErrorAction(action ErrorAction) *Consumer` — WithErrorAction — политика при ошибке handler-а.
  - `func (a *Consumer) WithPanicAction(action ErrorAction) *Consumer` — WithPanicAction — политика при panic в handler-е.
  - `func (a *Consumer) WithSentryPayloadLimit(maxBytes int) *Consumer` — WithSentryPayloadLimit — лимит payload (bytes) для sentry extras. 0 = без лимита.
- `type ErrorAction int` — ErrorAction определяет, что делать с сообщением при ошибке/панике.
  - константы: `ActionAck`, `ActionNack`
- `type Publisher struct`
  - `func NewAmqpPublisher(conn *amqp.ConnectionWrapper, cfg ...config.Config) (*Publisher, error)`
  - `func (p *Publisher) Close() error`
  - `func (p *Publisher) Publish(topic string, payload any) error` — Publish - отправка одного сообщения (topic = routing key для direct/topic, см. config ниже)
  - `func (p *Publisher) PublishBatch(topic string, payloads []any) error`
  - `func (p *Publisher) PublishBatchWithMetaData(topic string, meta map[string]string, payloads []any) error`
  - `func (p *Publisher) PublishWithMetaData(topic string, meta map[string]string, payload any) error`

