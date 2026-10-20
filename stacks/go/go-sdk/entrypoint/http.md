# HTTP Entrypoint (Gin)

## Правила

- **Хендлер** — всегда структура с методами, каждый метод возвращает `gin.HandlerFunc`; функции-хендлеры напрямую запрещены
- **HTTP ответы** — только через `gosdk-http-core/pkg/response`; `c.JSON(...)` напрямую запрещён
- **Валидация** — только через `validators` из `gosdk-http-core`; ручная валидация запрещена:
  ```go
  // GET / query-параметры
  request := indexRequest{}
  if ok := validators.ValidateRequestQuery(c, &request); !ok {
      return
  }

  // POST / PUT / PATCH / body
  request := createRequest{}
  if ok := validators.ValidateRequestBody(c, &request); !ok {
      return
  }
  ```
- **Request DTO** — обязателен embed `validation.Request`; тип всегда с маленькой буквы (unexported)
- **Response DTO** — обязателен embed `structures.Response[T]`; тип всегда с маленькой буквы (unexported)
- **Порядок в handler**: валидация → маппинг Request→Domain → вызов сервиса → маппинг Domain→Response → HTTP ответ
- Все эндпойнты покрыты Swagger-аннотациями: `@Security` у маршрута с auth и **все** коды, которые маршрут реально отдаёт (`regulations/rest-api.md`, «Документация»): 401/403 — за auth и право, 422 — валидация (и id пути), 404 — по ID, 400/413/415 — строгое тело. Для стандартных ошибок — готовые структуры SDK:
  ```go
  // @Failure 400 {object} structures.BadRequestErrorResponse
  // @Failure 401 {object} structures.BadRequestErrorResponse
  // @Failure 403 {object} structures.ForbiddenErrorResponse
  // @Failure 404 {object} structures.NotFoundErrorResponse
  // @Failure 413 {object} structures.BadRequestErrorResponse
  // @Failure 415 {object} structures.BadRequestErrorResponse
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

## Пакеты

Пути — как в `go.mod` эталона (источник GitLab; у проекта на GitHub — `github.com/exgamer/gosdk-http-core/…`, `../capabilities.md`):
- `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response`
- `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/validators`
- `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/gin/validation`
- `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware`
- `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/structures`

Эталон всех образцов ниже — `../../examples/tariff/internal/entrypoints/admin/http/billing/tariff/` (`request.go`, `mapper.go`, `handler.go`, `routes.go`).

## request.go

Обязателен embed `validation.Request`; все правила — в теге `binding` (валидатор gin, `SetTagName("binding")`; тег `validate` `ValidateRequest*` не читает — правило в нём молча не проверяется, тег находки `request-missing-bounds`; проверено тестом эталона `TestHandler_Index_PageBound`); границы — по `regulations/rest-api.md`, «Валидация входа»: `*_id` — `gt=0`, строки — `max`, числа — верхняя граница, enum — `oneof`, `page` — `lte=10000` (больше — 422), `per_page` — `lte=100`. `page` 0/не передан → 1 (SDK, `uint` 0 = «не передан»), `per_page` не передан → 30 (`../infra/postgres.md`, «Пагинация»):

```go
// indexRequest Фильтр списка тарифов
type indexRequest struct {
	validation.Request
	ParkingID uint `form:"parking_id" binding:"omitempty,gt=0"`
	Page      uint `form:"page" binding:"omitempty,lte=10000"`
	PerPage   uint `form:"per_page" binding:"omitempty,lte=100"`
}

// createRequest Создание тарифа
type createRequest struct {
	validation.Request
	ParkingID    uint   `json:"parking_id" binding:"required,gt=0"`
	Name         string `json:"name" binding:"required,max=255"`
	Currency     string `json:"currency" binding:"required,oneof=KZT RUB USD"`
	GraceMinutes uint   `json:"grace_minutes" binding:"lte=1440"`
	Price        uint   `json:"price" binding:"required,gt=0,lte=100000000"`
}
```

## response.go

Обязателен embed `structures.Response[T]`:

```go
// tariffData Тариф в ответе
type tariffData struct {
	ID        uint   `json:"id"`
	ParkingID uint   `json:"parking_id"`
	Name      string `json:"name"`
	Price     uint   `json:"price"`
}

type tariffResponse struct {
	structures.Response[tariffData]
}
```

## mapper.go

```go
func createRequestToParams(request createRequest) *tariffworkflow.CreateTariffParams {
	return &tariffworkflow.CreateTariffParams{
		ParkingID:    request.ParkingID,
		Name:         request.Name,
		Currency:     common.Currency(request.Currency),
		GraceMinutes: request.GraceMinutes,
		Price:        request.Price,
	}
}

func tariffToData(tariff *tariffdomain.Tariff) *tariffData {
	if tariff == nil {
		return nil
	}

	return &tariffData{
		ID:        tariff.ID,
		ParkingID: tariff.ParkingID,
		Name:      tariff.Name,
		Price:     tariff.Price,
	}
}
```

## handler.go

```go
func NewHandler(tariffService *tariffdomain.Service, createTariffWorkflow *tariffworkflow.CreateTariffWorkflow) *Handler {
	return &Handler{
		tariffService:        tariffService,
		createTariffWorkflow: createTariffWorkflow,
	}
}

type Handler struct {
	tariffService        *tariffdomain.Service
	createTariffWorkflow *tariffworkflow.CreateTariffWorkflow
}

// @Summary		Создать тариф
// @Description	Цена — не выше лимита парковки, иначе 422; parking_id недоступной парковки — 403
// @Tags			admin-tariff
// @Accept			json
// @Produce		json
// @Param			message	body	createRequest	true	"Тариф"
// @Security		BearerAuth
// @Success		201	{object}	tariffResponse
// @Failure		400	{object}	structures.BadRequestErrorResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
// @Failure		413	{object}	structures.BadRequestErrorResponse
// @Failure		415	{object}	structures.BadRequestErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/admin/v1/billing/tariffs [post]
func (h *Handler) Create() gin.HandlerFunc {
	return func(c *gin.Context) {
		request := createRequest{}
		if ok := validators.ValidateRequestBody(c, &request); !ok {
			return
		}

		scope, err := adminmiddleware.ParkingScopeFromContext(c.Request.Context())
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		if !scope.Allows(request.ParkingID) {
			response.ErrorResponseUntrackableSentry(c, http.StatusForbidden, errors.New("нет доступа к парковке"), nil)

			return
		}

		tariff, err := h.createTariffWorkflow.Exec(c.Request.Context(), createRequestToParams(request))
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.SuccessCreated(c, tariffToData(tariff))
	}
}
```

Получение path-параметра (`:id` в маршруте). Невалидный id — **422**, ошибку `GetIntQueryParam` перевести в `ValidationException` (`../exceptions.md`, «В хендлере»; тег `request-missing-bounds`; существующий `response.BadRequest` (400) — унаследованное, уровень — `approaches/process/review-tags.md`):

```go
id, err := validators.GetIntQueryParam(c, "id")
if err != nil {
	response.ErrorResponse(c, exception.NewValidationException(map[string]any{"id": "id должен быть положительным целым числом"}, false))

	return
}
```

> `GetIntQueryParam` несмотря на название читает **path-параметр** через `c.Param(name)` — это корректное использование для маршрутов вида `/:id`.

Swaggo-нотация для path-параметров — `:id` (Gin-стиль) или `{id}` в `@Router`, оба формата поддерживаются, не путать с ошибкой. Действие над записью — `POST` на подресурс-глагол (`regulations/rest-api.md`, «Методы»), не `DELETE`:
```go
// @Router /api/admin/v1/billing/tariffs/{id}/set-default [post]
```

## routes.go

URL — `/api/<context>/<version>/<domain>/<module>/<entity>` (`regulations/rest-api.md`, «URL»; модуль, совпадающий с сущностью, не повторяется): группа `/api` → `/<context>/<version>/<domain>` → маршруты сущности.

Обязательные middleware (отсутствие = ошибка ревью):
- `middleware.RequestInfoMiddleware(a)`
- `middleware.LoggerMiddleware()` — кроме группы с секретами в запросе (ниже)
- `middleware.DebugMiddleware()`
- `middleware.SentryMiddleware()`
- `middleware.MetricsMiddleware(a)`

`middleware.FormattedResponseMiddleware()` — тоже обязателен (тег `response-middleware-missing`): `response.Success`/`ErrorResponse`/`Raw*` только кладут данные в контекст, тело ответа пишет он — без него хендлер отдаёт пустой ответ. Не нужен только группе, где хендлеры не вызывают `response.*`.

`LoggerMiddleware` на DEBUG пишет тело запроса и заголовки целиком: группа с ручками, принимающими пароли, токены, секреты, ключи API (логин, смена пароля, HMAC, `Api-Key`), — без него или с исключением этих ручек, причина — комментарием в `routes.go` (`review-findings.md`, «Безопасность»; эталон — `examples/tariff/internal/entrypoints/integration/http/billing/tariff/routes.go`).

Порядок подключения обязателен: стек SDK → `FormattedResponseMiddleware` и метрики → auth (её отказы оборачивает `FormattedResponseMiddleware`, поэтому — после неё) → скоуп контекста → право на маршруте (`RequirePermissionMiddleware`) → `RequireJSONMiddleware` на маршрутах с телом (`../../structure.md`, «Entrypoint Layer») → хендлер:

```go
func SetRoutes(a *app.App, handler *Handler, adminAuth gin.HandlerFunc) error {
	router, err := di.GetRouter(a.Container)
	if err != nil {
		return err
	}

	service := router.Group("/api")
	{
		service.Use(middleware.RequestInfoMiddleware(a)) // заполнение request info
		service.Use(middleware.LoggerMiddleware())       // форматированные логи
		service.Use(middleware.DebugMiddleware())        // debug info в ответе
		service.Use(middleware.SentryMiddleware())       // отправка ошибок в Sentry

		billing := service.Group("/admin/v1/billing")
		{
			billing.Use(middleware.FormattedResponseMiddleware()) // форматированный ответ
			billing.Use(middleware.MetricsMiddleware(a))          // метрики Prometheus
			// Отказы auth-middleware оборачивает FormattedResponseMiddleware — они ставятся после неё
			billing.Use(adminAuth)
			billing.Use(adminmiddleware.ResolveAdminParkingsMiddleware()) // скоуп в context.Context

			billing.GET("/tariffs/:id", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.view"), handler.View())
			billing.POST("/tariffs", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.create"), adminmiddleware.RequireJSONMiddleware(), handler.Create())
			billing.POST("/tariffs/:id/set-default", adminmiddleware.RequirePermissionMiddleware("parking.admin.tariffs.update"), handler.SetDefault())
		}
	}

	return nil
}
```

`di` — `gosdk-http-core/pkg/di`.

## Response helpers

```go
response.Success(c, data)                  // 200
response.SuccessCreated(c, data)           // 201
response.SuccessDeleted(c, nil)            // 204
response.BadRequest(c, err, nil)           // 400
response.NotFound(c, err, nil)             // 404
response.InternalServerError(c, err, nil)  // 500
response.ErrorResponse(c, err)             // авто по типу AppException

response.ErrorResponseUntrackableSentry(c, http.StatusForbidden, err, nil) // заданный статус без отправки в Sentry: ожидаемые отказы 401/403/415 из middleware и хендлера
```

Список — `pagination.Paginated[{E}Data]` из SDK как есть (элементы переложены маппером, блок `Pagination` — тот, что вернул репозиторий): это эталонный формат списка для всех стеков (`regulations/rest-api.md`, «Формат ответа»). Свой тип ответа списка с другими ключами или пересчёт `pagination` — тег `pagination-format`. Эталон — `examples/tariff/internal/entrypoints/admin/http/billing/tariff/mapper.go` (`paginatedToData`).
