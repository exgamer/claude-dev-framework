# Миграции

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

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

## Ограничения внешнего ключа — всегда явно (`approaches/patterns/migrations.md`)

```php
$table->foreign('product_offer_id')
    ->references('id')
    ->on('product_offers')
    ->onUpdate('RESTRICT')
    ->onDelete('RESTRICT');
```

По умолчанию `RESTRICT` для обоих событий — явно указывать всегда.

---

## Миграция изменений (ALTER TABLE) с заполнением данных

Новая обязательная колонка в таблице с данными — три миграции: одна миграция — одно изменение, данные — отдельной миграцией (`approaches/patterns/migrations.md`).

```php
// 1. ..._add_payment_system_to_orders_table.php — колонка nullable
public function up(): void
{
    Schema::table($this->tableName, function (Blueprint $table) {
        $table->string('payment_system')->nullable()->comment('Платежная система');
    });
}

public function down(): void
{
    Schema::dropColumns($this->tableName, 'payment_system');
}
```

```php
// 2. ..._fill_payment_system_in_orders_table.php — данные
public function up(): void
{
    /** @var OrderRepositoryInterface $repository */
    $repository = app(OrderRepositoryInterface::class);
    $repository->fillPaymentSystem(PaymentMethodEnum::CARD, PaymentSystemEnum::EPAY);
}

public function down(): void
{
    // колонку вместе с данными удаляет down() миграции 1
}
```

```php
// 3. ..._make_payment_system_required_in_orders_table.php — снять nullable
public function up(): void
{
    Schema::table($this->tableName, function (Blueprint $table) {
        $table->string('payment_system')->nullable(false)->change();
    });
}

public function down(): void
{
    Schema::table($this->tableName, function (Blueprint $table) {
        $table->string('payment_system')->nullable()->change();
    });
}
```

Data-миграция пишет через типизированный метод репозитория или сервиса (`fillPaymentSystem(PaymentMethodEnum, PaymentSystemEnum)` — один `UPDATE … WHERE`, P-20), не массивом в `update()` ядра (`../conventions.md` п. 11a) и не через `getRepository()` у сервиса. Зависимость — `app(Interface::class)`: у миграции нет конструктора (`../conventions.md` п. 6). **Исключение — сырой SQL прямо в миграции:** перенос данных между схемами/таблицами, которых не знает ни один доменный репозиторий (`public` → схема подпроекта), и массовая правка одним `UPDATE … WHERE`/`INSERT … SELECT` без бизнес-решений; значения enum проверяются до переноса, откат — только перенесённое (эталон — `stacks/php/examples/parking_app/Infrastructure/Postgres/Billing/Providers/database/migrations/2026_10_20_100001_copy_tariffs_from_public.php`). Новый метод репозитория ради разовой миграции не заводится.
