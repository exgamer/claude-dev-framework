# Logger

`MPS\Utils\Components\Logger\Logger`

Расширяет `IlluminateLogger`. В не-debug режиме разбивает большой `$context` на части
по `starting_length` символов (`PART1`, `PART2`, ...) — чтобы вписаться в лимиты log-агрегатора.

## Использование

```php
use MPS\Core\Logger\LoggerAwareInterface;
use MPS\Core\Logger\LoggerAwareTrait;

class YourService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function doSomething(): void
    {
        $this->getLogger()->info('Message', ['key' => 'value']);
        $this->getLogger()->error('Something failed', ['error' => $e->getMessage()]);
    }
}
```

Инжектировать через DI, префикс генерируется автоматически из имени класса:

```php
$this->setLogger($logger); // вызывает $logger->for($this) внутри
```

Кастомный префикс:

```php
$this->getLogger()->for($this)->setPrefix('{CustomPrefix}');
```

## LogContext

`MPS\Utils\Components\Logger\LogContext` — статический глобальный контекст,
который автоматически примешивается к каждому лог-вызову (request_id, user_id и т.п.).

```php
LogContext::push(['request_id' => $requestId, 'user_id' => $userId]);
LogContext::clear(); // обязательно в middleware при Octane
```

## Известная проблема

`LogContext` хранит данные в `private static array $context`. При Octane контекст
предыдущего запроса утекает в следующий. Необходимо вызывать `LogContext::clear()`
в middleware в начале каждого запроса.
