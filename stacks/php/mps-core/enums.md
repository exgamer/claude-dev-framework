# Enums

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Где enum живёт — по владельцу понятия (`../structure.md`, дерево подпроекта; `../conventions.md` п. 12b): enum модуля — `Domains/{Domain}/Modules/{Module}/Enums/`, общий для модулей домена — `Domains/{Domain}/Common/Enums/`, общий для доменов — `Common/Enums/`, технический (ключи заголовков, кеша) — `Core/Enums/`.

**ОБЯЗАТЕЛЬНО для каждого enum:**
- `implements Enumerable` — всегда
- `use EnumerableTrait` — всегда, первой строкой в теле enum

## StatusEnum

```php
namespace App\YourApp\Domains\YourDomain\Modules\YourModule\Enums;

use MPS\Core\Enums\Enumerable;
use MPS\Core\Enums\EnumerableTrait;

enum YourStatusEnum: int implements Enumerable
{
    use EnumerableTrait;

    case ACTIVE = 1;
    case INACTIVE = 2;

    public static function labels(): array
    {
        return [
            self::ACTIVE->value => 'Активный',
            self::INACTIVE->value => 'Неактивный',
        ];
    }
}
```

## Swagger

Атрибутом (`../conventions.md` п. 18), не docblock-аннотацией:

```php
#[OA\Schema(schema: 'YourStatusEnum', type: 'integer', enum: [YourStatusEnum::ACTIVE->value, YourStatusEnum::INACTIVE->value])]
enum YourStatusEnum: int implements Enumerable
```

Ссылка в Request/Response: `ref: '#/components/schemas/YourStatusEnum'`. Новый кейс — обновить `enum:` и все места, где набор перечислен (`enum-list-drift`).
