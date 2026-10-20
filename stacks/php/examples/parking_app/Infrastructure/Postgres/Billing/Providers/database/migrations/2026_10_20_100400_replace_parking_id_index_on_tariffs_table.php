<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Индекс (parking_id) лишний: его покрывает (parking_id, created_at, id) из 2026_10_20_100300. Список без фильтра
 * по парковке (super-admin, сортировка по умолчанию -created_at) — индекс (created_at, id).
 *
 * DROP INDEX со схемой: dropIndex() Laravel на Postgres пишет имя без схемы, и вне search_path индекс не найдётся.
 */
return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    /** Имя, которое Laravel дал индексу index('parking_id') в 2026_10_07_100001: схема.таблица_колонка_index */
    private string $parkingIdIndex = 'parking_tariffs_parking_id_index';

    public function up(): void
    {
        DB::statement("DROP INDEX parking.{$this->parkingIdIndex}");

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'tariffs_created_at_id_index');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX parking.tariffs_created_at_id_index');

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->index('parking_id', $this->parkingIdIndex);
        });
    }
};
