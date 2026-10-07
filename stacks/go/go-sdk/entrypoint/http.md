# HTTP Entrypoint (Gin)

## Правила

- **Хендлер** — всегда структура с методами, каждый метод возвращает `gin.HandlerFunc`; функции-хендлеры напрямую запрещены
- **HTTP ответы** — только через `github.com/exgamer/gosdk-http-core/pkg/response`; `c.JSON(...)` напрямую запрещён
- **Валидация** — только через `validators` из `gosdk-http-core`; ручная валидация запрещена:
  ```go
  // GET / query-параметры
  request := indexRequest{}
  if ve := validators.ValidateRequestQuery(c, &request); ve == false {
      return
  }

  // POST / PUT / PATCH / body
  request := createRequest{}
  if ve := validators.ValidateRequestBody(c, &request); ve == false {
      return
  }
  ```
- **Request DTO** — обязателен embed `validation.Request`; тип всегда с маленькой буквы (unexported)
- **Response DTO** — обязателен embed `structures.Response[T]`; тип всегда с маленькой буквы (unexported)
- **Порядок в handler**: валидация → маппинг Request→Domain → вызов сервиса → маппинг Domain→Response → HTTP ответ
- Все эндпойнты покрыты Swagger-аннотациями; для стандартных ошибок использовать готовые структуры SDK:
  ```go
  // @Failure 400 {object} structures.BadRequestErrorResponse
  // @Failure 403 {object} structures.ForbiddenErrorResponse
  // @Failure 404 {object} structures.NotFoundErrorResponse
  // @Failure 422 {object} structures.ValidationErrorResponse
  // @Failure 500 {object} structures.InternalServerResponse
  ```
- **Middleware** — только именованные функции; анонимные функции внутри `Use(...)` запрещены. Стандартные — из `gosdk-http-core/pkg/middleware`:
  ```go
  // хорошо
  service.Use(middleware.RequestInfoMiddleware(a))
  router.Use(appInfoMiddleware(appInfo)) // своя, именованная, живёт в сервисе

  // плохо
  service.Use(func(c *gin.Context) { ... })
  ```
- **Своя middleware** — если она нужна одному сервису (особый роутер, свой listener, специфичная проверка), это именованная функция **в самом сервисе**, рядом с роутером или ядром, которому она нужна, с тестом. Не нарушение и не повод выносить в SDK. В `gosdk-http-core` выносится только то, что нужно нескольким сервисам (gateway, ревью 2026-09: `appInfoMiddleware` для data-plane listener осталась в шлюзе).

## Пакеты:
- `github.com/exgamer/gosdk-http-core/pkg/response`
- `github.com/exgamer/gosdk-http-core/pkg/validators`
- `github.com/exgamer/gosdk-http-core/pkg/middleware`
- `github.com/exgamer/gosdk-http-core/pkg/structures`

## request.go

Обязателен embed `validation.Request`:

```go
type calculateSessionDebtRequest struct {
    validation.Request
    ParkingId    uint   `json:"parking_id"    binding:"required" validate:"required"`
    LicensePlate string `json:"license_plate" binding:"required" validate:"required"`
}

type indexRequest struct {
    validation.Request
    ID      uint `form:"id"`
    Page    uint `form:"page"`
    PerPage uint `form:"per_page"`
}
```

## response.go

Обязателен embed `structures.Response[T]`:

```go
type debtCalcData struct {
    Debt *int `json:"debt"`
}

type calculateResponse struct {
    structures.Response[debtCalcData]
}
```

## mapper.go

```go
func calculateSessionDebtRequestToDTO(req calculateSessionDebtRequest) *tariff.CalculateSessionDebtParams {
    return &tariff.CalculateSessionDebtParams{
        ParkingId:    req.ParkingId,
        LicensePlate: req.LicensePlate,
    }
}

func debtCalcDataFromDebt(debt *int) *debtCalcData {
    return &debtCalcData{Debt: debt}
}
```

## handler.go

```go
func NewHandler(calculateSessionDebt *tariff.CalculateSessionDebt, tariffService *tariffdomain.Service) *Handler {
    return &Handler{
        calculateSessionDebt: calculateSessionDebt,
        tariffService:        tariffService,
    }
}

type Handler struct {
    calculateSessionDebt *tariff.CalculateSessionDebt
    tariffService        *tariffdomain.Service
}

// @Summary     Расчет долга для сессии
// @Tags        tariff
// @Accept      json
// @Produce     json
// @Param       message body calculateSessionDebtRequest true "Расчет долга"
// @Success     200 {object} calculateResponse
// @Failure     500 {object} structures.InternalServerResponse
// @Router      /api/v1/tariff/calculate-debt [post]
func (h *Handler) CalculateSessionDebt() gin.HandlerFunc {
    return func(c *gin.Context) {
        request := calculateSessionDebtRequest{}
        if ok := validators.ValidateRequestBody(c, &request); !ok {
            return
        }

        debt, err := h.calculateSessionDebt.Exec(c.Request.Context(), calculateSessionDebtRequestToDTO(request))
        if err != nil {
            response.ErrorResponse(c, err)

            return
        }

        response.Success(c, debtCalcDataFromDebt(debt))
    }
}
```

Получение path-параметра (`:id` в маршруте):

```go
id, err := validators.GetIntQueryParam(c, "id")
if err != nil {
    response.BadRequest(c, err, nil)

    return
}
```

> `GetIntQueryParam` несмотря на название читает **path-параметр** через `c.Param(name)` — это корректное использование для маршрутов вида `/:id`.

Swaggo-нотация для path-параметров использует `:id` (Gin-стиль) в `@Router`:
```go
// @Router /api/v1/tariff/invalidate-cache/:id [delete]
```
Это нормально — не путать с ошибкой. Swaggo поддерживает оба формата: `:id` и `{id}`.

## routes.go

Обязательные middleware (отсутствие = ошибка ревью):
- `middleware.RequestInfoMiddleware(a)`
- `middleware.LoggerMiddleware()`
- `middleware.DebugMiddleware()`
- `middleware.SentryMiddleware()`
- `middleware.MetricsMiddleware(a)`

`middleware.FormattedResponseMiddleware()` — указать если отсутствует, но не считать ошибкой: есть редкие случаи когда он не нужен.

Порядок подключения обязателен:

```go
func SetRoutes(a *app.App, handler *Handler) error {
    router, err := httpDi.GetRouter(a.Container)
    if err != nil {
        return err
    }

    service := router.Group("/api")
    service.Use(middleware.RequestInfoMiddleware(a)) // заполнение request info
    service.Use(middleware.LoggerMiddleware())        // форматированные логи
    service.Use(middleware.DebugMiddleware())         // debug info в ответе
    service.Use(middleware.SentryMiddleware())        // отправка ошибок в Sentry

    v1 := service.Group("/v1")
    v1.Use(middleware.FormattedResponseMiddleware())  // форматированный ответ
    v1.Use(middleware.MetricsMiddleware(a))           // метрики Prometheus

    v1.POST("/tariff/calculate-debt", handler.CalculateSessionDebt())
    v1.DELETE("/tariff/invalidate-cache/:id", handler.InvalidateCache())

    return nil
}
```

## Response helpers

```go
response.Success(c, data)                  // 200
response.SuccessCreated(c, data)           // 201
response.SuccessDeleted(c, nil)            // 204
response.BadRequest(c, err, nil)           // 400
response.NotFound(c, err, nil)             // 404
response.InternalServerError(c, err, nil)  // 500
response.ErrorResponse(c, err)             // авто по типу AppException
```
