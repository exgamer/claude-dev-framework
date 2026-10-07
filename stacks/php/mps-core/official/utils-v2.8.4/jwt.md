# Jwt

`MPS\Utils\Components\Jwt\JwtManager`

Минималистичная реализация JWT: генерация и валидация токенов с pluggable алгоритмом подписи.
По умолчанию используется HS256 (HMAC-SHA256).

## Использование

```php
use MPS\Utils\Components\Jwt\JwtManager;
use MPS\Utils\Components\Jwt\Signers\HS256Signer;

$jwt = new JwtManager(new HS256Signer());

$token = $jwt->generate(secret: 'my-secret', ttl: 3600, payload: []);
$valid  = $jwt->validate(token: $token, secret: 'my-secret'); // bool
```

`validate()` проверяет подпись и временной диапазон (`iat <= now < exp`).

В контейнере зарегистрирован через `ServiceProvider`:

```php
$this->app->bind(JwtManager::class, fn () => new JwtManager(new HS256Signer()));
```

## Кастомный алгоритм

Реализовать `JwtSignerInterface` и передать в `JwtManager`:

```php
use MPS\Utils\Components\Jwt\Contracts\JwtSignerInterface;

class RS256Signer implements JwtSignerInterface
{
    public function sign(string $data, string $secret): string { ... }
    public function verify(string $data, string $signature, string $secret): bool { ... }
    public function algorithm(): string { return 'RS256'; }
}

$jwt = new JwtManager(new RS256Signer());
```

## Дополнительные payload

```php
$token = $jwt->generate('secret', 3600, [
    'sub'  => $userId,
    'role' => 'stress-test',
]);
```

`iat` и `exp` добавляются автоматически и перезаписывают одноимённые ключи из `$payload`.
