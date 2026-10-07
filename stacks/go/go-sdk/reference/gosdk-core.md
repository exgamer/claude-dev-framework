# git.mpinnovations.kz/mps/go-packages/gosdk-core v1.0.5

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app`

- Переменные: `ErrKernelNotInited`
- Переменные: `ErrModuleNotInited`
- `type App struct`
  - `func NewApp() *App`
  - `func (app *App) AddStopHook(hook func(ctx context.Context) error)` — AddStopHook Добавить функцию, которая будет вызвана на shutdown
  - `func (app *App) Fail(err error)` — Fail — аварийная остановка
  - `func (app *App) GetContext() context.Context`
  - `func (app *App) InitKernel(name string) error` — InitKernel выполняет init kernel (один раз)
  - `func (app *App) InitKernels() error`
  - `func (app *App) InitModule(name string) error` — InitModule выполняет init модуля (один раз)
  - `func (app *App) InitModules() error`
  - `func (app *App) RegisterAndInitKernels(k ...KernelInterface) error`
  - `func (app *App) RegisterAndInitModules(m ...ModuleInterface) error`
  - `func (app *App) RegisterKernel(k KernelInterface) error` — RegisterKernel регистрирует kernel
  - `func (app *App) RegisterKernels(k ...KernelInterface) error`
  - `func (app *App) RegisterModule(m ModuleInterface) error` — RegisterModule регистрирует модуль
  - `func (app *App) RegisterModules(m ...ModuleInterface) error`
  - `func (app *App) RunAll() error`
  - `func (app *App) RunKernel(name string) error` — RunKernel запускает kernel
  - `func (app *App) SetEnvFiles(paths ...string) *App`
  - `func (app *App) WaitForShutdown()` — WaitForShutdown graceful
- `type KernelInterface interface{Name, Init, Start, Stop}`
- `type KernelManager struct`
  - `func NewKernelManager() *KernelManager`
  - `func (km *KernelManager) Init(app *App, name string) error` — Init выполняет Init(kernel) ровно один раз, остальные ждут завершения и получают ту же ошибку.
  - `func (km *KernelManager) InitAll(app *App) error`
  - `func (km *KernelManager) Register(k KernelInterface) error`
  - `func (km *KernelManager) RegisterAll(kernels ...KernelInterface) error`
  - `func (km *KernelManager) Run(app *App, name string) error` — Run гарантирует Init + Start (start тоже один раз, остальные ждут).
  - `func (km *KernelManager) RunAll(app *App) error`
- `type ModuleInterface interface{Name, Init}`
- `type ModuleManager struct`
  - `func NewModuleManager() *ModuleManager`
  - `func (m *ModuleManager) Init(app *App, name string) error`
  - `func (m *ModuleManager) InitAll(app *App) error`
  - `func (m *ModuleManager) Register(mod ModuleInterface) error`
  - `func (m *ModuleManager) RegisterAll(modules ...ModuleInterface) error`

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/config`

- `func InitConfig[E any](config *E) error` — InitConfig Инициализирует конфиг из переменок окружения
- `func LoadEnv(paths ...string) (string, error)` — LoadEnv читает env файлы по порядку + всегда включает OS ENV. Первый найденный файл используется.
- `type AppInfo struct` — AppInfo Данные приложения
  - `func GetInstanceAppInfo(appConfig *BaseConfig) *AppInfo`
- `type BaseConfig struct` — BaseConfig Основной конфиг приложения

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/constants`

- Константы: `AppInfoKey`
- Константы: `EnLang`
- Константы: `JSON`
- Константы: `KzLang`
- Константы: `LangCodeEN`
- Константы: `LangCodeKZ`
- Константы: `LangCodeRu`
- Константы: `RuLang`
- Константы: `XML`
- `func GetLanguageByCode(code string) int`
- `func GetLanguageCode(lang int) string`
- `func GetLanguages() []int`

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/context`

- `func GetAppInfoFromContext(ctx context.Context) *config.AppInfo`

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/debug`

- Переменные: `DebugCollectorKey`
- `func AddDebugStep(ctx context.Context, step string)` — AddDebugStep — удобный хелпер из бизнес-кода (где есть ctx)
- `func WithDebugCollector(ctx context.Context, dbg *DebugCollector) context.Context` — WithDebugCollector кладёт коллектор в context
- `type Category struct`
- `type DebugCollector struct`
  - `func GetDebugFromContext(ctx context.Context) *DebugCollector` — GetDebugFromContext достаёт коллектор из context.Context
  - `func NewDebugCollector() *DebugCollector`
  - `func (d *DebugCollector) AddStatement(cat string, duration time.Duration, stmt any)` — AddStatement добавляет statement в категорию и обновляет total по категории
  - `func (d *DebugCollector) AddStep(step string)` — AddStep добавляет шаг (удобно для бизнес-логики)
  - `func (d *DebugCollector) CalculateTotalTime()`
  - `func (d *DebugCollector) Cat(name string) *Category` — Cat возвращает категорию (создаёт при отсутствии)

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/di`

- Переменные: `ErrDependencyNotFound`, `ErrFactoryFailed`, `ErrCircularDependency`
- `func GetBaseConfig(c *Container) (*config.BaseConfig, error)` — GetBaseConfig возвращает BaseConfig.
- `func GetLocation(c *Container) (*time.Location, error)` — GetLocation возвращает Location Timezone.
- `func Has[T any](c *Container) bool` — Has - зарегистрирована ли зависимость (инстанс или фабрика). Фабрику не вызывает.
- `func HasNamed[T any](c *Container, name string) bool` — HasNamed - как Has, но для зависимости, зарегистрированной через RegisterNamed
- `func MustResolve[T any](c *Container) T` — MustResolve - как Resolve, но паникует ошибкой вместо её возврата. Для старта приложения, где без зависимости продолжать нельзя.
- `func MustResolveNamed[T any](c *Container, name string) T` — MustResolveNamed - как ResolveNamed, но паникует ошибкой вместо её возврата
- `func Register[T any](c *Container, instance T)` — Register - регистрирует зависимость (структуру, указатель, интерфейс или фабрику). Ключом служит статический тип T, а не динамический тип значения, поэтому интерфейс резолвится по интерфейсу: Register[Svc](c, impl) -> Re…
- `func RegisterNamed[T any](c *Container, name string, instance T)` — RegisterNamed - как Register, но под именем: несколько зависимостей одного типа. Пустое имя - то же, что Register.
- `func Resolve[T any](c *Container) (T, error)` — Resolve - получает зависимость. Фабрика вызывается при первом запросе ровно один раз, результат кешируется как синглтон под запрошенным типом T. Конкурентные запросы ждут результата этого вызова. Если фабрика вернула оши…
- `func ResolveNamed[T any](c *Container, name string) (T, error)` — ResolveNamed - как Resolve, но для зависимости, зарегистрированной через RegisterNamed
- `type Container struct` — Container - контейнер зависимостей с поддержкой фабрик
  - `func NewContainer() *Container` — NewContainer - конструктор контейнера
  - `func (c *Container) Close(ctx context.Context) error` — Close - закрывает объекты, созданные фабриками контейнера, в обратном порядке создания: зависимый объект закрывается раньше своих зависимостей. Готовые инстансы, переданные в Register, не закрываются - ими владеет тот, к…

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter`

- `func Capture(ctx context.Context, err error, opts Options) error` — Capture отправляет ошибку в error-трекер с явно указанными опциями и возвращает ту же ошибку, помеченную как отправленная - это позволяет репортить и пробрасывать ошибку одной строкой:
- `func CaptureError(ctx context.Context, err error, tags map[string]string) error` — CaptureError фиксирует ошибку с уровнем error и возвращает её обратно, готовую к пробросу наверх - удобно для сценария "выбить ошибку и отправить в Sentry" одной строкой:
- `func CaptureSoft(ctx context.Context, err error, tags map[string]string) error` — CaptureSoft фиксирует некритичную ошибку и НЕ прерывает выполнение. Используется, когда у вызывающего кода есть fallback и саму ошибку не нужно пробрасывать наверх - например Redis недоступен, но можно сходить в БД напря…
- `func Flush(timeout time.Duration) bool` — Flush синхронно ждёт отправки уже поставленных в очередь событий, если текущий Reporter это поддерживает (реализует Flusher). Для noop-репортера или реализации без поддержки Flush - no-op, возвращает true сразу.
- `func IsConfigured() bool` — IsConfigured сообщает, зарегистрирован ли реальный (не noop) Reporter. Полезно, если код хочет заранее понять, что репортинг реально куда-то улетит.
- `func SetReporter(r Reporter)` — SetReporter регистрирует активную реализацию Reporter. Вызывается один раз адаптером при инициализации (например SentryKernel.Init).
- `func WasReported(err error) bool` — WasReported сообщает, что ошибка уже была отправлена в error-трекер через этот пакет. Используется адаптерами верхних слоёв (например HTTP-транспортом), чтобы не отправить один и тот же инцидент дважды, когда ошибка репо…
- `type Flusher interface{Flush}` — Flusher - опциональный интерфейс для Reporter. Некоторые реализации (например sentry-go) отправляют события асинхронно в фоновой горутине - без явного ожидания перед завершением процесса последнее событие можно потерять.…
- `type Level string` — Level - уровень серьёзности события для error-репортера
  - константы: `LevelWarning`, `LevelError`, `LevelFatal`
- `type Options struct` — Options - дополнительные данные события
- `type Reporter interface{Capture}` — Reporter - абстракция над системой трекинга ошибок (Sentry, Rollbar и т.п.). gosdk-core ничего не знает про конкретного вендора - реальную реализацию регистрирует соответствующий адаптер (например SentryKernel из gosdk-s…

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception`

- `type AppException struct` — AppException Модель данных для описания ошибки
  - `func NewAppException(err error, context map[string]any, trackInSentry bool) *AppException`
  - `func NewForbiddenException(err error, trackInSentry bool) *AppException`
  - `func NewNotFoundException(err error, trackInSentry bool) *AppException`
  - `func NewValidationException(context map[string]any, trackInSentry bool) *AppException`
  - `func (e *AppException) Error() string`
  - `func (e *AppException) Unwrap() error`
- `type ErrorKind string`
  - константы: `ErrorKindValidation`, `ErrorKindNotFound`, `ErrorKindForbidden`, `ErrorKindInternal`

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/helpers`

- `func GetDurationAsString(duration time.Duration) string` — GetDurationAsString - Возвращает Duration в виде строки с указанием секунды, миллисекунды и тп

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/logger`

- `func Debug(ctx context.Context, msg string)`
- `func Dump(ctx context.Context, v ...any)`
- `func Error(ctx context.Context, msg string)`
- `func Fatal(ctx context.Context, msg string)`
- `func Info(ctx context.Context, msg string)`
- `func Init()`
- `func IsDebugLevel() bool`
- `func IsTraceLevel() bool`
- `func SetLevel(level Level)`
- `func SetLogger(custom *stdlog.Logger)`
- `func Trace(ctx context.Context, msg string)`
- `func Warning(ctx context.Context, msg string)`
- `type Level int`
  - `func GetLevel() Level`
  - `func ParseLevel(name string) Level`
  - `func (l Level) String() string`
  - константы: `LevelTrace`, `LevelDebug`, `LevelInfo`, `LevelWarn`, `LevelError`, `LevelFatal`

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/regex`

- `func IsLatinCyrillicWithSpaces(s string) bool` — IsLatinCyrillicWithSpaces - проверяет чтоыб строка содержала только кирилицу, латиницу, пробел и цифры
- `func StringIsPositiveInt(param string) error` — StringIsPositiveInt - проверяет является ли строка положительным int

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/slice`

- `func Filter[T any](slice []T, filter func(T) bool) []T` — Filter - Фильтрация слайса
- `func Map[T any, Y any](slice []T, callback func(int, T) Y) []Y` — Map - Созание нового слайса
- `func RemoveAt[T any](slice []T, index int) []T` — RemoveAt - удаляет элемент по индексу из слайса без сохранения порядка. Если индек не корректный возвращает слайс без изменений
- `func RemoveAtOrderly[T any](slice []T, index int) []T` — RemoveAtOrderly - удаляет элемент по индексу из слайса, сохраняя порядок. Если индек не корректный возвращает слайс без изменений
- `func RemoveDuplicates[T comparable](input []T) []T`
- `func RemoveMultiple[T any](slice []T, filter func(T) bool) []T` — RemoveMultiple - удаляет элементы, соответствующие условию фильтрации.
- `func StructToMap(obj interface{}) (newMap map[string]interface{}, err error)` — StructToMap - Превращает структуру в map[string]interface{} используя json

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/structures`

- `func GetFieldsAsJsonTags(str interface{}) []string`
- `func GetFieldsAsMapStructureTags(str interface{}) []string`
- `func GetFieldsAsUpperSnake(str interface{}) []string`
- `func GetStructName(structure interface{}) string` — GetStructName - Возвращает название структуры

## `git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/validation`

- `func CheckValidEmail(email string) bool` — CheckValidEmail - проверка адреса email
- `func CheckValidPhone(phone string) bool` — CheckValidPhone - проверка номера телефона
- `func NormalizePhoneNumber(phone string) (string, error)` — нормализация номера телефона "+7 (775)-557-70-41" -> 77755577041, "+77755577041" -> 77755577041, "87755577041" -> 77755577041, "8(775)557-70-41" -> 77755577041, "" -> The phone number does not match the format

