# Миграции

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## Структура файла

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private string $tableName = 'your_table';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->smallInteger('status')->default(1);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->nullable();
        });

        DB::statement("CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON {$this->tableName} FOR EACH ROW EXECUTE PROCEDURE on_update_current_timestamp()");
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
```

**Обязательные правила:**
- Анонимный класс: `return new class () extends Migration`
- `private string $tableName` — всегда, имя таблицы через свойство
- Триггер `on_update_current_timestamp` — для каждой таблицы с `updated_at`
- `created_at` — `default(DB::raw('CURRENT_TIMESTAMP'))`, не `$table->timestamps()`
- `updated_at` — `nullable()`, обновляется триггером

---

## Именование файлов

```
YYYY_MM_DD_HHMMSS_{описание}.php
```

Примеры:
```
2024_02_21_000003_basket_table.php
2024_08_06_000000_orders_edits.php
2025_02_04_101010_add_is_primary_and_status_to_category_tree_table.php
```

Где хранятся: `Infrastructure/Postgres/{Domain}/Providers/database/migrations/`

---

## Ограничения внешнего ключа (Foreign Key Constraints) обязательны если возможно

```php
$table->foreign('product_offer_id')
    ->references('id')
    ->on('product_offers')
    ->onUpdate('RESTRICT')
    ->onDelete('RESTRICT');
```

По умолчанию `RESTRICT` для обоих событий — явно указывать всегда.

---

## Миграция изменений (ALTER TABLE)

```php
public function up(): void
{
    Schema::table($this->tableName, function (Blueprint $table) {
        $table->string('payment_system')->nullable()->comment('Платежная система');
    });

    // Заполнение данных через сервис
    /** @var OrderServiceInterface $orderService */
    $orderService = app(OrderServiceInterface::class);
    $orderService->getRepository()->update(
        condition: ['payment_method' => PaymentMethodEnum::CARD->value],
        data: ['payment_system' => PaymentSystemEnum::EPAY->value]
    );

    // Снять nullable после заполнения
    Schema::table($this->tableName, function (Blueprint $table) {
        $table->string('payment_system')->nullable(false)->change();
    });
}

public function down(): void
{
    Schema::dropColumns($this->tableName, 'payment_system');
}
```

Можно использовать `app(ServiceInterface::class)` для data-миграций.
