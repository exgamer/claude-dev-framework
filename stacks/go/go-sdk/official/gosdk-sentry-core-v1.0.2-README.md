# gosdk-sentry-core

Адаптер Sentry поверх `errorreporter` из `gosdk-core`. Единственный пакет
в SDK, который импортирует `sentry-go` напрямую. Ни `gosdk-http-core`,
ни `gosdk-rabbit-core`, ни код приложения про Sentry ничего не знают -
они зовут только `errorreporter.Capture`/`CaptureSoft`/`CaptureError`.

## Установка

```bash
go get git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core
```

## Подключение

```go
import (
    "git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"
    sentrykernel "git.mpinnovations.kz/mps/go-packages/gosdk-sentry-core/pkg/app"
)

appInstance.RegisterAndInitKernels(
    &sentrykernel.SentryKernel{},
    // ... остальные кернелы (Http, Rabbit, Postgres)
)
```

Порядок регистрации кернелов не важен - `SentryKernel.Init` только
регистрирует `Reporter` глобально, а реальные отправки происходят позже
(на запросах/сообщениях), когда все кернелы уже проинициализированы.

## ENV

```bash
SENTRY_DSN=https://xxx@sentry.io/yyy
```

Если `SENTRY_DSN` не задан, `SentryKernel.Init` не возвращает ошибку -
приложение стартует как обычно, просто `errorreporter` остаётся no-op.

## Что делает

- `sentry.Init(...)` с `AttachStacktrace: true`, `TracesSampleRate: 1.0` (как раньше в `HttpKernel`).
- Регистрирует `errorreporter.Reporter`, который на каждый `Capture`:
  - ставит теги `environment`/`service` из `AppInfo` в контексте;
  - добавляет теги и контексты из `errorreporter.Options`;
  - маппит `errorreporter.Level` в `sentry.Level`;
  - зовёт `sentry.CaptureException`.
- На `Stop` делает `sentry.Flush(2 * time.Second)`, чтобы не терять события при shutdown.

## Важно: breaking change при апгрейде

Раньше `gosdk-http-core` сам инициализировал Sentry, если было выставлено
`SENTRY_DSN`. Теперь это поведение убрано - `HttpKernel`/`Consumer` только
зовут `errorreporter.Capture`, а инициализация Sentry - явная зона
ответственности `SentryKernel`. Если не зарегистрировать `SentryKernel`,
события в Sentry просто не будут уходить (без ошибок, без паники - тихо).

При обновлении `gosdk-http-core`/`gosdk-rabbit-core` обязательно добавьте
`SentryKernel` в список кернелов, если сервис использует Sentry.