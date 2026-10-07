# git.mpinnovations.kz/mps/go-packages/gosdk-http-core v1.0.11

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/app`

- Константы: `HttpKernelName`
- `type HttpKernel struct`
  - `func (m *HttpKernel) Init(a *app.App) error`
  - `func (m *HttpKernel) Name() string`
  - `func (m *HttpKernel) Start(a *app.App) error`
  - `func (m *HttpKernel) Stop(ctx context.Context) error`

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/config`

- `type HttpConfig struct` — HttpConfig Http конфиг
- `type HttpInfo struct` — HttpInfo Данные http
  - `func (s *HttpInfo) GenerateRequestId()`

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/constants`

- Константы: `NotFound`, `AccessDenied`, `OperationFailed`, `IncorrectParams`, `ValidationError`, `InternalServerError`, `Unauthorized`, `MethodNotAllowed`, `RequestTimeout`, `Conflict`, `PayloadTooLarge`, `TooManyRequests`, `…`
- Константы: `RequestIdHeaderName`, `LanguageHeaderName`, `CityHeaderName`, `UserHeaderName`, `AppsflyerHeaderName`, `CurrentCompanyIdHeaderName`, `CompanyIdHeaderName`, `CompanyIdsHeaderName`, `IinHeaderName`, `CacheControlHeaderName`, `OriginHeaderName`, `AuthorizationHeaderName`
- Константы: `HttpInfoKey`
- `func GetErrorTypeByStatusCode(statusCode int) string` — GetErrorTypeByStatusCode возвращает тип ошибки для респонза по хттп статус коду. Статус без отдельного типа: 4xx — ClientError, остальное — InternalServerError.

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di`

- `func GetHttpConfig(c *di.Container) (*config.HttpConfig, error)` — GetHttpConfig возвращает HTTP Config.
- `func GetMetricsCollector(c *di.Container) (*metrics.Collector, error)` — GetMetricsCollector возвращает MetricsCollector.
- `func GetRouter(c *di.Container) (*gin.Engine, error)` — GetRouter возвращает HTTP router.

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/exception`

- `func ValidationErrorsAsMap(validationErrors validate.Errors) map[string]any` — ValidationErrorsAsMap -возвращает ошибки валидации как map
- `type HttpException struct` — HttpException Модель данных для описания HTTP-ошибки приложения.
  - `func NewHttpException(code int, err error, context map[string]any) *HttpException`
  - `func NewInternalServerErrorException(err error, context map[string]any) *HttpException`
  - `func NewUntrackableHttpException(code int, err error, context map[string]any) *HttpException`
  - `func NewValidationAppExceptionFromValidationErrors(validationErrors validate.Errors) *HttpException`
  - `func NewValidationHttpException(context map[string]any) *HttpException`
  - `func (e *HttpException) Error() string`
  - `func (e *HttpException) GetErrorType() string`
  - `func (e *HttpException) Unwrap() error`

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/gin`

- `func CaptureToSentry(c *gin.Context, err error)` — CaptureToSentry отправляет ошибку в error-трекер (Sentry и т.п.) с контекстом запроса через errorreporter.Capture. Сам пакет ничего не знает про Sentry - реальную отправку делает адаптер, зарегистрированный через errorre…
- `func ErrorHandler(c *gin.Context, err any)` — ErrorHandler Обработчик ошибок gin
- `func GetHttpInfoFromContext(ctx context.Context) *config.HttpInfo`
- `func GetInstanceHttpInfo(c *gin.Context) *config.HttpInfo`
- `func InitRouter(baseConfig *baseConfig.BaseConfig, httpConfig *config.HttpConfig) *gin.Engine` — InitRouter Базовая инициализация gin

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/gin/validation`

- `type IRequest interface{ValidationMessage, CustomValidationMessage, CustomValidationRules}` — IRequest - интерфейс для HTTP запросов
- `type Request struct` — Request - HTTP запрос
  - `func (r *Request) CustomValidationMessage(fe validator.FieldError) string`
  - `func (r *Request) CustomValidationRules() map[string]validator.Func`
  - `func (r *Request) ValidationMessage(fe validator.FieldError) string`

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/helpers`

- Константы: `DefaultPaginationPage`
- Константы: `DefaultPaginationPerPage`
- `type PagerRequest struct`
  - `func GetPagerRequest(ctx *gin.Context) (*PagerRequest, error)`

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/logger`

- `func FormattedError(ctx context.Context, method string, uri string, status int, requestId string, message string)` — FormattedError Форматированный лог ошибки
- `func FormattedErrorWithHttpInfo(ctx context.Context, httpInfo *config2.HttpInfo, message string)` — FormattedErrorWithHttpInfo Форматированный лог ошибки для RequestData
- `func FormattedInfo(ctx context.Context, method string, uri string, status int, requestId string, message string)` — FormattedInfo Форматированный лог
- `func FormattedLogWithHttpInfo(ctx context.Context, httpInfo *config2.HttpInfo, message string)` — FormattedLogWithHttpInfo Форматированный лог для RequestData

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/metrics`

- Константы: `MetricNameHttpRequest`, `MetricLabelHttpStatus`, `MetricLabelHttpMethod`, `MetricLabelHttpUrl`
- `type Collector struct`
  - `func NewCollector(serviceName string) *Collector`
  - `func (m *Collector) GetMetrics(statusCode int, method string, path string, duration float64)`

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/middleware`

- `func CorsMiddleware(a *app.App) gin.HandlerFunc` — CorsMiddleware разрешает cross-origin запросы. Origins берутся из env CORS_ALLOWED_ORIGINS (список через запятую). Значение "*" разрешает все origins.
- `func DebugMiddleware() gin.HandlerFunc` — DebugMiddleware кладёт DebugCollector в context запроса и считает TotalTime в конце
- `func FormattedResponseMiddleware() gin.HandlerFunc` — FormattedResponseMiddleware Middleware для обработки ответа
- `func LoggerMiddleware() gin.HandlerFunc` — LoggerMiddleware Middleware для логирования ответа и отправки ошибок в сентри
- `func MetricsMiddleware(a *app.App) gin.HandlerFunc` — MetricsMiddleware - мидлвар для обработки HTTP запросов метрик
- `func RequestInfoMiddleware(a *app.App) gin.HandlerFunc` — RequestInfoMiddleware Middleware заполняющий данные запроса
- `func SentryMiddleware() gin.HandlerFunc` — SentryMiddleware Middleware для обработки ошибок в sentry

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response`

- `func BadRequest(c *gin.Context, err error, ctx map[string]any)`
- `func BadRequestRaw(c *gin.Context, err error, ctx map[string]any)`
- `func Conflict(c *gin.Context, err error, ctx map[string]any)`
- `func ConflictRaw(c *gin.Context, err error, ctx map[string]any)`
- `func ErrorResponse(c *gin.Context, err error)`
- `func ErrorResponseRaw(c *gin.Context, err error)`
- `func ErrorResponseUntrackableSentry(c *gin.Context, statusCode int, err error, context map[string]any)`
- `func ErrorResponseUntrackableSentryRaw(c *gin.Context, statusCode int, err error, context map[string]any)`
- `func ErrorResponseWithStatus(c *gin.Context, statusCode int, err error, context map[string]any)`
- `func ErrorResponseWithStatusRaw(c *gin.Context, statusCode int, err error, context map[string]any)`
- `func Forbidden(c *gin.Context, err error, ctx map[string]any)`
- `func ForbiddenRaw(c *gin.Context, err error, ctx map[string]any)`
- `func Formatted(c *gin.Context)`
- `func FormattedRawResponse(c *gin.Context, data any)`
- `func FormattedSuccessResponse(c *gin.Context, data any)`
- `func InternalServerError(c *gin.Context, err error, ctx map[string]any)`
- `func InternalServerErrorRaw(c *gin.Context, err error, ctx map[string]any)`
- `func NotFound(c *gin.Context, err error, ctx map[string]any)`
- `func NotFoundRaw(c *gin.Context, err error, ctx map[string]any)`
- `func Raw(c *gin.Context, data any, statusCode int)` — Raw кладёт data как есть (без обёртки success/data) с произвольным статусом.
- `func RawCreated(c *gin.Context, data any)`
- `func RawDeleted(c *gin.Context, data any)`
- `func RawSuccess(c *gin.Context, data any)`
- `func Success(c *gin.Context, data any)`
- `func SuccessCreated(c *gin.Context, data any)`
- `func SuccessDeleted(c *gin.Context, data any)`
- `func TooManyRequests(c *gin.Context, err error, ctx map[string]any)`
- `func TooManyRequestsRaw(c *gin.Context, err error, ctx map[string]any)`
- `func Unauthorized(c *gin.Context, err error, ctx map[string]any)`
- `func UnauthorizedRaw(c *gin.Context, err error, ctx map[string]any)`
- `func UnprocessableEntity(c *gin.Context, err error, ctx map[string]any)`
- `func UnprocessableEntityRaw(c *gin.Context, err error, ctx map[string]any)`

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/structures`

- `type BadRequestErrorResponse struct` — BadRequestErrorResponse Структура описывает ответ для 400
- `type ForbiddenErrorResponse struct` — ForbiddenErrorResponse Структура описывает ответ для 403
- `type HttpResponse struct` — HttpResponse Модель описывающая ответ от rest запроса
- `type InternalServerResponse struct` — InternalServerResponse Структура описывает ответ для 500
- `type NotFoundErrorResponse struct` — NotFoundErrorResponse Структура описывает ответ 404 400
- `type Response struct`
- `type ValidationErrorResponse struct` — ValidationErrorResponse Структура описывает ответ для 422

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/validators`

- `func BindANdValidateStruct[T any](byte []byte, i *T) (map[string]string, error)` — BindANdValidateStruct - биндит в структуру массив битов и валидирует
- `func GetIntQueryParam(c *gin.Context, name string) (int, error)`
- `func ValidateRequestBody(c *gin.Context, request validation.IRequest) bool` — ValidateRequestBody - Валидация тела HTTP реквеста
- `func ValidateRequestQuery(c *gin.Context, request validation.IRequest) bool` — ValidateRequestQuery - Валидация GET параметров HTTP реквеста

