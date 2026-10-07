# ItemsContainer

> **deprecated since 2.2.1** — использовать `MPS\Core\Components\ItemsContainer\ItemsContainer`

`MPS\Utils\Components\ItemsContainer\ItemsContainer`

Контейнер для коллекции DataObject-ов с Aware-трейтом.

## Миграция

```php
// ❌ Устарело
use MPS\Utils\Components\ItemsContainer\ItemsContainer;
use MPS\Utils\Components\ItemsContainer\ItemsContainerAwareTrait;

// ✅ Актуально
use MPS\Core\Components\ItemsContainer\ItemsContainer;
use MPS\Core\Components\ItemsContainer\ItemsContainerTrait;
```

API идентичен — `getItems()`, `getIds()`, `clear()`, итерируемый.
