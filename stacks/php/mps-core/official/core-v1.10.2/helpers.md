# Helpers

`MPS\Core\Helpers`

---

## AssertHelper

Расширяет `webmozart/assert`. Все провальные проверки бросают `InvalidConfigurationAppException` вместо стандартного `InvalidArgumentException`.

```php
use MPS\Core\Helpers\AssertHelper;

AssertHelper::notNull($value, 'Value must not be null');
AssertHelper::isArray($config, 'Config must be an array');
AssertHelper::inArray($status, $allowed, 'Invalid status');
```

---

## EnvHelper

Проверка текущего окружения через `EnvEnum`.

```php
use MPS\Core\Helpers\EnvHelper;
use MPS\Core\Enums\EnvEnum;

if (EnvHelper::is(EnvEnum::Production)) {
    // только в продакшне
}
```

---

## InstanceHelper

Работа с объектами через Reflection.

### isExists

Проверяет существование класса. При `$throw = true` бросает `InvalidConfigurationAppException`.

```php
InstanceHelper::isExists(SomeClass::class);             // null если нет
InstanceHelper::isExists(SomeClass::class, throw: true); // исключение если нет
```

### instanceOf

Проверяет что класс реализует интерфейс.

```php
InstanceHelper::instanceOf(SomeClass::class, SomeInterface::class, throw: true);
```

### shortName

Короткое имя класса (без namespace), с опциональным обрезанием суффикса.

```php
InstanceHelper::shortName(CategoryService::class, 'Service'); // 'Category'
InstanceHelper::shortName(CategoryService::class, 'Service', capitalize: true); // 'CATEGORY'
```

### createObject

Создаёт объект из массива. Ключ `className` в массиве или явный второй аргумент.

```php
$obj = InstanceHelper::createObject(['className' => SomeClass::class, 'prop' => 'value']);
$obj = InstanceHelper::createObject($data, SomeClass::class);
```

### objectFromArray

Заполняет уже существующий объект из массива (подробнее — в [data-objects.md](data-objects.md)).

```php
$object = InstanceHelper::objectFromArray($object, $data);
```

### dataObjectFromArray

Специализированная версия для `DataObject`.

```php
$dto = InstanceHelper::dataObjectFromArray($dataObject, $data);
```

---

## JsonHelper

Удобная обёртка над `json_encode` / `json_decode`.

```php
JsonHelper::encode(['key' => 'value']);        // UTF-8 без экранирования слэшей
JsonHelper::decode('{"key":"value"}');          // associative array
JsonHelper::htmlEncode(['key' => 'value']);     // с HEX-экранированием для HTML
JsonHelper::isJson($string, $decoded);         // проверка + декодирование по ссылке
JsonHelper::getSizeInBytes(['key' => 'value']); // размер JSON в байтах
```

---

## ReflectionHelper

Кэширующая обёртка над `ReflectionClass`.

```php
ReflectionHelper::reflection(SomeClass::class); // ReflectionClass с кэшем (до 100 классов)
ReflectionHelper::getProperty($reflectionClass, 'propertyName'); // поиск без учёта регистра и _
```

Маппинг свойств нечувствителен к регистру и подчёркиваниям: `user_id`, `UserId`, `userId` — всё находит одно и то же свойство.

---

## RequestHelper

Хелперы для получения стандартных query-параметров.

```php
RequestHelper::getPageParam();        // query('page')
RequestHelper::getPerPageQueryParam(); // query('per_page')
RequestHelper::getSortParam();         // query('sort')
RequestHelper::getQueryParam('key');   // любой query-параметр
```

---

## ResponseHelper

Разбор и сборка `{ success, data }` JSON-ответов.

```php
ResponseHelper::parse($payload);        // [$success, $data]
ResponseHelper::isSuccess($payload);    // bool
ResponseHelper::parseData($payload);    // только data
ResponseHelper::wrap($data, $success);  // собрать ответ
ResponseHelper::empty($success);        // пустой Response-объект
```

---

## DBSortHelper

Утилита для парсинга параметра сортировки (строка с опциональным `-` префиксом).

```php
DBSortHelper::getClearAttribute('-created_at'); // 'created_at'
DBSortHelper::getSortingOrder('-created_at');   // 'DESC'
DBSortHelper::getSortingOrder('name');           // 'ASC'
```

> Помечен как `@todo: не место ему тут` — используется внутри `CRUDRepository`.

---

## TransformHelper

Заглушка для будущей реализации перегонки объектов и массивов. Тело пустое.
