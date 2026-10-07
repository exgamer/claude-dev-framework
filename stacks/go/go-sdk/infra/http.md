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
.SetRequestHeaders(map[string]string{"key": "value"})
.SetQueryParams(map[string]string{"page": "1"})
.SetRequestTimeout(5 * time.Second)  // default: 30s
.SetJSONBody(payload)                // Content-Type: application/json
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

- **5xx** → `GetResult()` возвращает `error`
- **4xx** → не ошибка, бизнес-логика; проверять через `resp.IsClientError()`

## Проброс Request ID (обязателен) — отсутствие = ошибка ревью

```go
httpInfo := gin.GetHttpInfoFromContext(ctx)
builder.NewGetHttpRequestBuilder[ResponseType](ctx, url).
    SetRequestHeaders(map[string]string{
        constants.RequestIdHeaderName: httpInfo.RequestId,
    })
```

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
    SetRequestHeaders(map[string]string{
        constants.RequestIdHeaderName: httpInfo.RequestId,
    }).
    SetQueryParams(map[string]string{"page": "1"}).
    GetResult()
```

### POST

```go
resp, err := builder.NewPostHttpRequestBuilder[builder.Response[city]](ctx, url).
    SetRequestHeaders(map[string]string{
        constants.RequestIdHeaderName: httpInfo.RequestId,
    }).
    SetJSONBody(payload).
    GetResult()
```

PUT / PATCH — аналогично POST, только другой конструктор.

## repository.go

```go
func NewHttpRepository() *HttpRepository {
    return &HttpRepository{}
}

type HttpRepository struct{}

func (r *HttpRepository) GetById(ctx context.Context, id uint) (*domain.City, error) {
    httpInfo := gin.GetHttpInfoFromContext(ctx)

    resp, err := builder.NewGetHttpRequestBuilder[builder.Response[city]](
        ctx,
        "http://catalog-service/api/v1/cities/"+strconv.Itoa(int(id)),
    ).SetRequestHeaders(map[string]string{
        constants.RequestIdHeaderName: httpInfo.RequestId,
    }).GetResult()

    if err != nil {
        return nil, err
    }

    return modelToEntity(&resp.Result.Data), nil
}
```
