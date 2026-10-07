# MessageBag

`MPS\Utils\Components\Error\MessageBag`

Коллектор уникальных ошибок с подсчётом повторений. Используется в QueryFilters V2
для сбора ошибок валидации фильтров.

## Использование

```php
use MPS\Utils\Components\Error\MessageBag;
use MPS\Utils\Components\Error\MessageBagAwareTrait;

class YourService
{
    use MessageBagAwareTrait;

    public function process(array $items): void
    {
        foreach ($items as $item) {
            if (! $item['valid']) {
                $this->messageBag->set('Invalid item: ' . $item['id']);
            }
        }

        if ($this->messageBag->count() > 0) {
            // обработать ошибки
            foreach ($this->messageBag as $item) {
                $this->logger->warning($item->message(), ['count' => $item->count()]);
            }
        }
    }
}
```

## Методы

```php
$bag->set(string $message): self    // добавить / инкрементировать счётчик
$bag->get(string $message): ?MessageBagItem
$bag->all(): MessageBagItem[]
$bag->count(): int
$bag->clear(): self
```

`MessageBagItem` — `hash()`, `message()`, `count()`, `increment()`, `decrement()`.
