# Enums

`MPS\Core\Enums`

---

## Enumerable — интерфейс и трейт

Пакет предоставляет `Enumerable` интерфейс и `EnumerableTrait` с расширенным API для работы с enum'ами. Работает как с нативными PHP 8.1 enum'ами, так и со старым стилем (константы в классе).

### Подключение

**Нативный enum (рекомендуется):**

```php
use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

enum StatusEnum: int implements Enumerable
{
    use EnumerableTrait;

    case ACTIVE   = 1;
    case DISABLED = 0;

    public static function labels(): array
    {
        return [
            self::ACTIVE->value   => 'Активный',
            self::DISABLED->value => 'Отключён',
        ];
    }
}
```

**Старый стиль (класс с константами):**

```php
use MPS\Core\Enums\Enum;

class RoleEnum extends Enum
{
    public const ADMIN  = 'admin';
    public const CLIENT = 'client';

    public static function labels(): array
    {
        return [
            self::ADMIN  => 'Администратор',
            self::CLIENT => 'Клиент',
        ];
    }
}
```

### API

```php
StatusEnum::all()      // [ACTIVE => 1, DISABLED => 0]  — все кейсы/константы
StatusEnum::values()   // [1, 0]                         — только значения
StatusEnum::keys()     // ['ACTIVE', 'DISABLED']         — только ключи
StatusEnum::labels()   // [1 => 'Активный', 0 => 'Отключён']
StatusEnum::list()     // [1 => 'Активный', 0 => 'Отключён'] — для select
StatusEnum::toString() // JSON-строка всех значений

StatusEnum::label(1)          // 'Активный'
StatusEnum::label(99, 'N/A')  // 'N/A' — значение по умолчанию

StatusEnum::ACTIVE->getLabel()   // 'Активный'  — instance-метод
StatusEnum::ACTIVE->hasLabel()   // true
```

**`list()`** — удобно для select-опций:

```php
StatusEnum::list()                    // [1 => 'Активный', 0 => 'Отключён']
StatusEnum::list(reverse: true)       // ['Активный' => 1, 'Отключён' => 0]
StatusEnum::list(labelsAsKeys: true)  // ['Активный' => 'Активный', ...]
```

**`exclude()`** — исключить значения из `all()` и `list()`:

```php
public static function exclude(): array
{
    return [self::LEGACY->value];
}
```

---

## Встроенные enum'ы

### StatusEnum

```php
use MPS\Core\Enums\StatusEnum;

StatusEnum::ACTIVE   // 1
StatusEnum::DISABLED // 0
```

> Старый стиль (класс с константами). В проекте используется нативный PHP enum — см. `.claude/instructions/enums.md`.

### EnvEnum

```php
use MPS\Core\Enums\EnvEnum;
use MPS\Core\Helpers\EnvHelper;

EnvEnum::LOCAL    // 'local'
EnvEnum::STAGE    // 'stage'
EnvEnum::TESTING  // 'testing'
EnvEnum::PROD     // 'prod'
EnvEnum::PRE_PROD // 'preprod'

EnvEnum::fromAppConfig()        // читает app.env из конфига
EnvHelper::is(EnvEnum::PROD)    // bool — проверка текущего окружения
```

### LocaleEnum

```php
use MPS\Core\Enums\LocaleEnum;

LocaleEnum::RU // 'ru-RU'
LocaleEnum::KK // 'kk-KZ'
LocaleEnum::EN // 'en-US'
```

### LanguageStringEnum / LanguageNumberEnum

Два представления языков — строковое и числовое:

```php
LanguageStringEnum::RU        // 'ru'
LanguageStringEnum::KK        // 'kk'
LanguageStringEnum::EN        // 'en'
LanguageStringEnum::default() // 'ru'

LanguageNumberEnum::RU        // 1
LanguageNumberEnum::KK        // 2
LanguageNumberEnum::EN        // 3
LanguageNumberEnum::default() // 1
```

### CurrencyEnum

```php
CurrencyEnum::KZT // 'KZT'
CurrencyEnum::USD // 'USD'
```

### CountryCodeEnum

```php
CountryCodeEnum::KZ // 'KZ'
```

### StringCaseEnum

Используется в `toArray()` и `validated()` для выбора регистра ключей:

```php
StringCaseEnum::DEFAULT // без преобразования
StringCaseEnum::SNAKE   // snake_case
StringCaseEnum::CAMEL   // camelCase
StringCaseEnum::KEBAB   // kebab-case
```
