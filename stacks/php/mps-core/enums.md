# Enums

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

Enum-ы живут в `Domains/{Domain}/Modules/{Module}/Enums/`.

**ОБЯЗАТЕЛЬНО для каждого enum:**
- `implements Enumerable` — всегда
- `use EnumerableTrait` — всегда, первой строкой в теле enum

## StatusEnum

```php
namespace App\Domains\YourDomain\Modules\YourModule\Enums;

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

## Swagger-аннотация

```php
/**
 * @OA\Schema(
 *     schema="YourStatusEnum",
 *     type="integer",
 *     enum={1, 2},
 * )
 */
enum YourStatusEnum: int implements Enumerable
```

Ссылка в Request/Response: `ref="#/components/schemas/YourStatusEnum"`.
