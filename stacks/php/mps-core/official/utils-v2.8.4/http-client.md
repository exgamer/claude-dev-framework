# HttpClient

`MPS\Utils\Components\Http\HttpClient`

Обёртка над Laravel HTTP + Guzzle с единой обработкой ошибок.

## Методы

```php
$client->get(string $url, array $params = []): array
$client->post(string $url, array $data = []): array
$client->put(string $url, array $data = []): array
$client->patch(string $url, array $data = []): array
$client->delete(string $url, array $data = []): array
```

## Конфигурация

```php
$client
    ->setRetry(3)
    ->setTimeOut(30)
    ->setConnectTimeout(5)
    ->setHeaders(['Authorization' => 'Bearer ' . $token]);
```

## HttpRepository

Базовый класс для HTTP-интеграций. Наследовать и определить `getHost()` / `getBaseUrl()`:

```php
use MPS\Utils\Components\Http\Repository\HttpRepository;

class CategoryRepository extends HttpRepository
{
    protected function getHost(): string
    {
        return 'http://application.host';
    }

    protected function getBaseUrl(): string
    {
        return '/api/v1/category';
    }

    public function getTree(?int $parentId = null): array
    {
        $response = $this->get('/tree', ['parent_id' => $parentId]);

        return $this->extract($response); // распаковывает { success, data }
    }
}
```

`extract()` — разворачивает стандартный ответ `{ success, data }` и возвращает `data`.

## Concurrent requests

Три способа отправить параллельные запросы:

**1. Через `$this->concurrent()` внутри HttpRepository:**

```php
$responses = $this->concurrent(function () use ($parentIds) {
    $requests = [];
    foreach ($parentIds as $parentId) {
        $requests["parent{$parentId}"] = $this->getTree($parentId);
    }

    return $requests;
});
```

**2. Через `HttpClientFactory` (из нескольких репозиториев):**

```php
use MPS\Utils\Components\Http\Factories\HttpClientFactory;

$client = HttpClientFactory::makeClient();
$responses = $client->concurrent(function () use ($repos, $ids) {
    return array_map(fn ($id) => $repos[0]->getTree($id), $ids);
});
```

**3. Через `ConcurrentRequestManagerInterface`:**

```php
$manager = app(ConcurrentRequestManagerInterface::class);

$manager->add('prices', fn () => $client->get('/prices', ['ids' => $ids]));
$manager->add('stocks', fn () => $client->get('/stocks', ['ids' => $ids]));

$results = $manager->execute();
// $results['prices'], $results['stocks']
```

## Известная проблема

`applyConfig()` использует `static $isApply` — статическая переменная в методе.
При Octane конфиг применяется один раз на весь процесс, последующие смены конфига игнорируются.
