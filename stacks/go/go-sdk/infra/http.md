# HTTP Infrastructure

Пакет: `github.com/exgamer/gosdk-http-request-builder/pkg/builder`

## Конструкторы

```go
builder.NewGetHttpRequestBuilder[E](ctx, url)
builder.NewPostHttpRequestBuilder[E](ctx, url)
builder.NewPutHttpRequestBuilder[E](ctx, url)
builder.NewPatchHttpRequestBuilder[E](ctx, url)
builder.NewDeleteHttpRequestBuilder[E](ctx, url)
```

## Конфигурация

```go
.SetRequestHeaders(map[string]string{"key": "value"}) // ЗАМЕНЯЕТ все заголовки — вызывать до SetJSONBody/SetXMLBody
.SetQueryParams(map[string]string{"page": "1"})
.SetRequestTimeout(5 * time.Second)  // default: 30s
.SetJSONBody(payload)                // добавляет Content-Type: application/json к уже заданным заголовкам
.SetXMLBody(payload)                 // Content-Type: application/xml
.SetThrowUnmarshalError(false)       // не возвращать ошибку если тело не JSON
```

## Выполнение

```go
resp, err := b.GetResult()  // выполнить и распарсить тело в E
err        := b.Do()        // выполнить без парсинга тела
```

## Ответ

```go
resp.Result      // E — распарсенное тело
resp.StatusCode  // int — HTTP статус
resp.Body        // []byte — сырое тело

resp.IsSuccess()      // 2xx
resp.IsClientError()  // 4xx
resp.IsServerError()  // 5xx
```

## Поведение ошибок

- `GetResult()` / `Do()` возвращают `error` только при сбое транспорта (сеть, таймаут, отмена контекста) и — с `SetThrowUnmarshalError(true)` — при неразбираемом теле. **Статус ответа ошибкой не считается**: и 4xx, и 5xx приходят с `err == nil` (сверено по `gosdk-http-request-builder` v1.0.2).
- Поэтому после `GetResult()` статус проверяется **всегда**: `!resp.IsSuccess()` → ошибка адаптера; 4xx (`resp.IsClientError()`) — ответ внешней системы, который сценарий может разобрать (не найдено, отказ), 5xx (`resp.IsServerError()`) — сбой внешней системы. Использовать `resp.Result` без проверки статуса — **[ОШИБКА]** `http-status-unchecked`.

## Проброс Request ID (обязателен) — отсутствие = ошибка ревью

`gin.GetHttpInfoFromContext(ctx)` возвращает `nil` вне HTTP-запроса (consumer, консоль, тикер, `context.WithoutCancel` после ответа) — `httpInfo.RequestId` без проверки = паника. Заголовок собирается одним хелпером проекта (не-бизнесовая функциональность — один раз, `conventions.md` п. 22), а не проверкой в каждом адаптере:

```go
// pkg/requestid/requestid.go
// Header — заголовок Request ID для исходящего запроса; вне HTTP-запроса — пустой набор.
func Header(ctx context.Context) map[string]string {
    if info := gin.GetHttpInfoFromContext(ctx); info != nil && info.RequestId != "" {
        return map[string]string{constants.RequestIdHeaderName: info.RequestId}
    }

    return map[string]string{}
}

builder.NewGetHttpRequestBuilder[ResponseType](ctx, url).
    SetRequestHeaders(requestid.Header(ctx))
```

Своя проверка `httpInfo != nil` в двух и более адаптерах — **[ОШИБКА]** (инфраструктурный workaround, `stacks/go/conventions.md` п. 22).

## model.go

```go
type city struct {
    ID   uint   `json:"id"`
    Name string `json:"name"`
}
```

## mapper.go

Маппер обязателен. Имена функций — `modelToEntity`, если моделей несколько — по смыслу.

```go
func modelToEntity(m *city) *domain.City {
    if m == nil { return nil }

    return &domain.City{ID: m.ID, Name: m.Name}
}
```

## Примеры запросов

### GET

```go
resp, err := builder.NewGetHttpRequestBuilder[builder.Response[city]](ctx, url).
    SetRequestHeaders(requestid.Header(ctx)).
    SetQueryParams(map[string]string{"page": "1"}).
    GetResult()
```

### POST

```go
resp, err := builder.NewPostHttpRequestBuilder[builder.Response[city]](ctx, url).
    SetRequestHeaders(requestid.Header(ctx)). // до SetJSONBody — иначе Content-Type затрётся
    SetJSONBody(payload).
    GetResult()
```

PUT / PATCH — аналогично POST, только другой конструктор.

## repository.go

Адаптер пакета `internal/infrastructure/http/{provider}/{product}/` называется по роли порта (`architecture/integrations.md`, В-18): источник данных — `HTTPRepository` в `repository.go`, порт отправки (уведомление, публикация без данных в ответ) — `HTTPNotifier` в `notifier.go` (не `Client`, не `HttpRepository`, не `Gateway`); реализует порт домена или workflow неявно. Эталон — `examples/tariff/internal/infrastructure/http/cityparking/pricelimits/repository.go` (порт данных), `.../partnerbilling/tariffs/notifier.go` (порт отправки). Ниже — источник данных; у `HTTPNotifier` та же форма (конструктор с адресом и таймаутом, `POST` с телом из `mapper.go`).

**Клиент.** `gosdk-http-request-builder` создаёт `http.Client` сам на каждый запрос (сеттера клиента нет, сверено по v1.0.2), поэтому для него правило «клиент инжектируется» (`conventions.md` п. 15, `architecture/integrations.md`) означает: адрес и таймаут приходят через конструктор из конфига модуля (`app/bootstrap/{domain}/{module}/config.go`), а не литералом в репозитории; таймаут — `SetRequestTimeout` на каждый запрос.

**Конфиг.** Адрес проверяется и нормализуется (абсолютный http(s), без хвостового `/`) при чтении конфига, не в конструкторе адаптера (`regulations/service-requirements.md`, «Конфиг»); таймаут в env — целые секунды, в `time.Duration` переводит bootstrap (`config.InitConfig` `time.Duration` из env не разбирает — сверено по `gosdk-core` v1.0.9). Эталон — `examples/tariff/internal/app/bootstrap/billing/tariff/config.go`.

```go
func NewHTTPRepository(baseURL string, timeout time.Duration) *HTTPRepository {
    return &HTTPRepository{
        baseURL: baseURL,
        timeout: timeout,
    }
}

type HTTPRepository struct {
    baseURL string
    timeout time.Duration
}

func (r *HTTPRepository) GetByID(ctx context.Context, id uint) (*domain.City, error) {
    resp, err := builder.NewGetHttpRequestBuilder[builder.Response[city]](
        ctx,
        fmt.Sprintf("%s/api/v1/cities/%d", r.baseURL, id),
    ).
        SetRequestHeaders(requestid.Header(ctx)).
        SetRequestTimeout(r.timeout).
        SetThrowUnmarshalError(false).
        GetResult()
    if err != nil {
        return nil, fmt.Errorf("get city failed (id=%d): %w", id, err)
    }

    if resp.StatusCode == http.StatusNotFound {
        return nil, nil
    }

    if !resp.IsSuccess() {
        return nil, fmt.Errorf("get city failed (id=%d): status %d", id, resp.StatusCode)
    }

    return modelToEntity(&resp.Result.Data), nil
}
```
