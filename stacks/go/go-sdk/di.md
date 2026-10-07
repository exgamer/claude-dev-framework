# DI — dependency injection

Пакет: `github.com/exgamer/gosdk-core/pkg/di`

Контейнер хранится в `a.Container` (поле `*app.App`). Регистрация и резолв:

```go
di.Register(a.Container, myService)
svc, err := di.Resolve[*MyService](a.Container)
```

## Правила

- Контейнер используется только внутри `App` (bootstrap, модули)
- Никогда не передавай `a.Container` в бизнес-логику или репозитории
- Резолвить зависимости через хелперы конкретного kernel — не через `di.Resolve` напрямую

## Что доступно из DI — core kernel
Пакет: `github.com/exgamer/gosdk-core/pkg/di`
```go
location, err   := di.GetLocation(a.Container)    // *time.Location
baseConfig, err := di.GetBaseConfig(a.Container)  // *config.BaseConfig
```

## Что доступно из DI — postgres kernel
Пакет: `github.com/exgamer/gosdk-postgres-core/pkg/di`
```go
// основной коннект
conn, err := postgresDi.GetDefaultPostgresConnection(a.Container) // *gorm.DB

// добавить дополнительный коннект
err := postgresDi.AddPostgresConnection(a.Container, "name", &config.PostgresDbConfig{})

// получить дополнительный коннект
conn, err := postgresDi.GetPostgresConnection(a.Container, "name") // *gorm.DB
```

## Что доступно из DI — http kernel
Пакет: `github.com/exgamer/gosdk-http-core/pkg/di`
```go
router, err     := httpDi.GetRouter(a.Container)           // *gin.Engine
httpConfig, err := httpDi.GetHttpConfig(a.Container)       // *config.HttpConfig
metColl, err    := httpDi.GetMetricsCollector(a.Container) // *metrics.Collector
```

## Что доступно из DI — redis kernel
Пакет: `github.com/exgamer/gosdk-redis-core/pkg/di`
```go
redisClient, err := redisDi.GetRedisClient(a.Container) // *redis.Client
```

## Что доступно из DI — rabbit kernel
Пакет: `github.com/exgamer/gosdk-rabbit-core/pkg/di`
```go
rabbitClient, err  := rabbitDi.GetRabbitClient(a.Container)             // *rabbitmq.Consumer
consumersReg, err  := rabbitDi.GetRabbitConsumersRegistry(a.Container)  // *app.ConsumersRegistry
publishersReg, err := rabbitDi.GetRabbitPublishersRegistry(a.Container) // *app.PublisherRegistry
```
