# git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core v1.0.2

## `git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/app`

- Константы: `SentryKernelName`
- `type SentryKernel struct` — SentryKernel - единственное место в приложении, которое инициализирует sentry-go и регистрирует его как errorreporter.Reporter. Не зависит от HttpKernel/RabbitKernel и не требует их - работает для любой комбинации кернел…
  - `func (k *SentryKernel) Init(a *coreApp.App) error`
  - `func (k *SentryKernel) Name() string`
  - `func (k *SentryKernel) Start(a *coreApp.App) error`
  - `func (k *SentryKernel) Stop(ctx context.Context) error`

## `git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/config`

- `type SentryConfig struct` — SentryConfig - настройки подключения к Sentry, читаются из ENV.

## `git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/sentry`

- `type Reporter struct` — Reporter - реализация errorreporter.Reporter поверх sentry-go. Единственное место в SDK, где вызывается sentry.CaptureException - любой вызывающий код (http-core, rabbit-core, приложение) ничего не знает про Sentry, толь…
  - `func NewReporter() *Reporter`
  - `func (r *Reporter) Capture(ctx context.Context, err error, opts errorreporter.Options)`
  - `func (r *Reporter) Flush(timeout time.Duration) bool` — Flush - реализация errorreporter.Flusher. Синхронно ждёт отправки накопленных в фоновой горутине sentry-go событий - нужно вызывать перед выходом из one-shot процессов (консольные команды), у которых нет штатного gracefu…

