# Модель

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

Модель — только маппинг на таблицу. Никакой бизнес-логики.

```php
namespace App\Infrastructure\YourDomain\YourModule\Models;

use MPS\Core\Models\Model;

class YourModel extends Model
{
    protected $table = 'your_table';

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status'     => YourStatusEnum::class,
        'created_at' => 'datetime',
    ];

    public function relation(): HasMany
    {
        return $this->hasMany(RelatedModel::class, 'your_model_id');
    }
}
```

## Что допустимо

| Что | Пример |
|---|---|
| `$table`, `$primaryKey`, `$timestamps` | базовые свойства |
| `$fillable` / `$guarded` | защита mass assignment |
| `$casts` | типизация колонок, cast в Enum |
| `$appends` + accessor | простое вычисление без зависимостей |
| Relations | только те, что используются в `with()` в Repository |

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

Добавлять relation только если он **явно используется** через `with()` в `filterSearch` Repository.
Не добавлять "на будущее" — связи создают неявные зависимости и соблазн обратиться к ним из любого места минуя Repository.

