# Модель

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

Модель — только маппинг на таблицу. Никакой бизнес-логики.

```php
namespace App\ParkingApp\Infrastructure\Postgres\YourDomain\YourModule\Models;

use MPS\Core\Models\Model;

/**
 * Ваша сущность
 *
 * @property int $id                  PK
 * @property string $name             Название
 * @property YourStatusEnum $status   Статус
 * @property Carbon $created_at       Дата создания
 */
class YourModel extends Model
{
    protected $table = 'your_schema.your_table';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status'     => YourStatusEnum::class,
        'created_at' => 'datetime',
    ];
}
```

Связей (`hasMany`, `belongsTo`…) в модели нет — раздел «Relations» ниже. Эталон — `../examples/parking_app/Infrastructure/Postgres/Billing/Tariffs/Models/Tariff.php`.

## Что допустимо

| Что | Пример |
|---|---|
| `$table`, `$primaryKey`, `$timestamps` | базовые свойства |
| `$fillable` / `$guarded` | защита mass assignment |
| `$casts` | типизация колонок, cast в Enum |
| `$appends` + accessor | простое вычисление без зависимостей |
| Relations | нет — связанные данные собираются через репозитории (`../conventions.md`, «Модели и внешние системы») |

## Что запрещено

```php
// ❌ Scopes — фильтрация принадлежит Repository
public function scopeActive(Builder $query): Builder
{
    return $query->where('status', StatusEnum::ACTIVE);
}

// ❌ Бизнес-логика в мутаторе
public function setStatusAttribute(int $value): void
{
    if ($value === StatusEnum::ACTIVE->value) {
        $this->sendNotification(); // side-effect
    }
    
    $this->attributes['status'] = $value;
}

// ❌ boot() с side-effects
protected static function boot(): void
{
    parent::boot();
    static::creating(fn ($model) => $model->uuid = Str::uuid()); // логика в модели
}

// ❌ Static бизнес-методы
public static function findActiveByUser(int $userId): Collection
{
    return static::where('user_id', $userId)->where('status', 1)->get();
}
```

## Relations

Eloquent-связей (`BelongsTo`, `HasMany`…) в моделях нет, `with()` не используется (тег `model-has-eloquent-relations`): связи создают неявные запросы и соблазн обратиться к данным в обход Repository. Связанные данные — методом репозитория пачкой по id, сборка — в сервисе.

