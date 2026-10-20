<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Outbox событий о тарифах. Индексы — под внешние ключи и под выборку повтора (незавершённые статусы по id).
 */
return new class () extends Migration {
    private string $tableName = 'parking.outbox_tariff_events';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tariff_id')->comment('ID тарифа');
            $table->unsignedBigInteger('parking_id')->comment('ID парковки');
            $table->string('name')->comment('Название тарифа на момент события');
            $table->string('currency', 3)->comment('Валюта');
            $table->unsignedBigInteger('price')->comment('Цена часа в минимальных единицах валюты');
            $table->unsignedSmallInteger('grace_minutes')->nullable()->comment('Льготный период (минуты)');
            $table->string('routing_key', 64)->comment('Routing key публикации: tariff.created');
            $table->string('status', 20)->comment('Статус: pending, published, failed, awaiting_retry');
            $table->unsignedSmallInteger('attempts')->default(0)->comment('Неудачных попыток отправки');
            $table->timestamp('published_at')->nullable()->comment('Когда брокер принял событие');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->nullable();

            $table->index('tariff_id');
            $table->index('parking_id');

            $table->foreign('tariff_id')
                ->references('id')
                ->on('parking.tariffs')
                ->onUpdate('RESTRICT')
                ->onDelete('RESTRICT');

            $table->foreign('parking_id')
                ->references('id')
                ->on('parking.parkings')
                ->onUpdate('RESTRICT')
                ->onDelete('RESTRICT');
        });

        DB::statement("CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON {$this->tableName} FOR EACH ROW EXECUTE PROCEDURE parking.on_update_current_timestamp()");

        // Частичный индекс — у Blueprint его нет: выборка повтора идёт только по ждущим отправки
        DB::statement("CREATE INDEX outbox_tariff_events_retry_idx ON {$this->tableName} (id) WHERE status IN ('pending', 'awaiting_retry')");
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
