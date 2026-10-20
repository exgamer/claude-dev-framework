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
- Зависимости kernel (подключения, роутер, конфиг) — через хелперы kernel (`postgresDI.GetDefaultPostgresConnection`, `redisDI.GetRedisClient`, `di.GetRouter`), не через `di.Resolve`; свои типы — `di.Resolve[T]` в bootstrap/модуле (как в `examples/tariff`, `bootstrap/billing/tariff/module.go`)

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
conn, err := postgresDI.GetDefaultPostgresConnection(a.Container) // *gorm.DB

// добавить дополнительный коннект
err := postgresDI.AddPostgresConnection(a.Container, "name", &config.PostgresDbConfig{})

// получить дополнительный коннект
conn, err := postgresDI.GetPostgresConnection(a.Container, "name") // *gorm.DB
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
redisClient, err := redisDI.GetRedisClient(a.Container) // *redis.Client
```

## Что доступно из DI — rabbit kernel
Пакет: `github.com/exgamer/gosdk-rabbit-core/pkg/di`
```go
rabbitClient, err  := rabbitDI.GetRabbitClient(a.Container)             // *rabbitmq.Consumer
consumersReg, err  := rabbitDI.GetRabbitConsumersRegistry(a.Container)  // *app.ConsumersRegistry
publishersReg, err := rabbitDI.GetRabbitPublishersRegistry(a.Container) // *app.PublisherRegistry
```
