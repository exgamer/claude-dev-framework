# git.mpinnovations.kz/mps/go-packages/gosdk-http-request-builder v1.0.1

## `git.mpinnovations.kz/mps/go-packages/gosdk-http-request-builder/pkg/builder`

- `type HttpRequestBuilder struct`
  - `func NewDeleteHttpRequestBuilder[E any](ctx context.Context, url string) *HttpRequestBuilder[E]`
  - `func NewGetHttpRequestBuilder[E any](ctx context.Context, url string) *HttpRequestBuilder[E]`
  - `func NewPatchHttpRequestBuilder[E any](ctx context.Context, url string) *HttpRequestBuilder[E]`
  - `func NewPostHttpRequestBuilder[E any](ctx context.Context, url string) *HttpRequestBuilder[E]`
  - `func NewPutHttpRequestBuilder[E any](ctx context.Context, url string) *HttpRequestBuilder[E]`
  - `func (b *HttpRequestBuilder[E]) Do() error`
  - `func (b *HttpRequestBuilder[E]) GetResult() (*HttpResponse[E], error)`
  - `func (b *HttpRequestBuilder[E]) SetJSONBody(v any) *HttpRequestBuilder[E]`
  - `func (b *HttpRequestBuilder[E]) SetQueryParams(q map[string]string) *HttpRequestBuilder[E]`
  - `func (b *HttpRequestBuilder[E]) SetRequestHeaders(h map[string]string) *HttpRequestBuilder[E]`
  - `func (b *HttpRequestBuilder[E]) SetRequestTimeout(t time.Duration) *HttpRequestBuilder[E]`
  - `func (b *HttpRequestBuilder[E]) SetThrowUnmarshalError(v bool) *HttpRequestBuilder[E]`
  - `func (b *HttpRequestBuilder[E]) SetXMLBody(v any) *HttpRequestBuilder[E]`
- `type HttpResponse struct` — HttpResponse Модель описывающая ответ от rest запроса
  - `func (r *HttpResponse[E]) IsClientError() bool`
  - `func (r *HttpResponse[E]) IsServerError() bool`
  - `func (r *HttpResponse[E]) IsSuccess() bool`
- `type HttpStatement struct`
- `type Response struct`

