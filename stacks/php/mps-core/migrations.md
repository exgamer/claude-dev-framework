# Миграции

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Структура файла

Образец — миграция эталона `../examples/parking_app/Infrastructure/Postgres/Billing/Providers/database/migrations/2026_10_07_100001_create_tariffs_table.php` (колонки `currency`, `price`, `is_default`, `grace_minutes` опущены):

```php
<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parking_id')->comment('ID парковки');
            $table->string('name')->comment('Название тарифа');
            $table->string('status', 20)
                ->default(TariffStatusEnum::ACTIVE->value)
                ->comment('Статус: ' . TariffStatusEnum::ACTIVE->value . ', ' . TariffStatusEnum::ARCHIVED->value);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->index('parking_id');

            $table->foreign('parking_id')
                ->references('id')
                ->on('parking.parkings')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        DB::statement("CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON {$this->tableName} FOR EACH ROW EXECUTE PROCEDURE parking.on_update_current_timestamp()");
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
```

**Обязательные правила:**
- `declare(strict_types=1);` — как в любом PHP-файле (`../checklist.md` п. 33)
- Анонимный класс: `return new class () extends Migration`
- `private string $tableName` — всегда, имя таблицы через свойство; схема — как в проекте: таблицы подпроекта в своей схеме — со схемой (в эталоне `parking.tariffs`; `approaches/patterns/migrations.md`), иначе без
- `created_at` и `updated_at` — желательно у каждой таблицы; как заполняются — как принято в проекте. В эталоне: `created_at` — `default(DB::raw('CURRENT_TIMESTAMP'))`, `updated_at` — `nullable()`, обновляется триггером `on_update_current_timestamp`, функция — в схеме подпроекта (`parking.on_update_current_timestamp()`, миграция `Infrastructure/Postgres/Common/Providers/database/migrations/`): подпроект не зависит от функции в `public`
- Внешние ключи — явно (раздел ниже); `comment()` у колонок данных — как в эталоне
- Удаление индекса — таблица в своей схеме — `DB::statement('DROP INDEX <схема>.<имя>')` (схема подпроекта, как в `$tableName`), не `$table->dropIndex()`: Laravel на Postgres пишет `drop index "<имя>"` без схемы, и вне `search_path` миграция падает; имя индекса по колонкам — `<схема>_<таблица>_<колонки>_index` (эталон — `2026_10_20_100400_replace_parking_id_index_on_tariffs_table.php`)

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
    ->onUpdate('restrict')
    ->onDelete('restrict');
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
// 2. ..._fill_payment_system_in_orders_table.php — данные: разовая правка — сырой SQL одним UPDATE, значения — из enum
public function up(): void
{
    DB::update(
        "UPDATE {$this->tableName} SET payment_system = ? WHERE payment_method = ? AND payment_system IS NULL",
        [PaymentSystemEnum::EPAY->value, PaymentMethodEnum::CARD->value],
    );
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

Data-миграция: **разовая правка без бизнес-решений** (заполнить колонку, массовый `UPDATE … WHERE`/`INSERT … SELECT`, перенос между схемами/таблицами, которых не знает ни один доменный репозиторий, — `public` → схема подпроекта) — **сырой SQL прямо в миграции**, плейсхолдеры `?`, значения — из enum; значения enum проверяются до переноса, откат — только перенесённое (эталон — `stacks/php/examples/parking_app/Infrastructure/Postgres/Billing/Providers/database/migrations/2026_10_20_100001_copy_tariffs_from_public.php`). Новый метод репозитория ради разовой миграции не заводится. Метод репозитория или сервиса — только когда он **уже есть** или нужен домену и вне миграции (типизированный, один `UPDATE … WHERE`, P-20); не массивом в `update()` ядра (`../conventions.md` п. 11a) и не через `getRepository()` у сервиса; зависимость — `app(Interface::class)`: у миграции нет конструктора (`../conventions.md` п. 6).
