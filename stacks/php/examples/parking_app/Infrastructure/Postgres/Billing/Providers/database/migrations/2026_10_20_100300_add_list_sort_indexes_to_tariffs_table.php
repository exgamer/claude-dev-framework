<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Индексы под список тарифов парковки: фильтр по parking_id, сортировка по name или created_at, затем id.
 */
return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->index(['parking_id', 'created_at', 'id'], 'tariffs_parking_id_created_at_id_index');
            $table->index(['parking_id', 'name', 'id'], 'tariffs_parking_id_name_id_index');
        });
    }

    /**
     * DROP INDEX со схемой: dropIndex() Laravel на Postgres пишет имя без схемы, и вне search_path индекс не найдётся.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX parking.tariffs_parking_id_created_at_id_index');
        DB::statement('DROP INDEX parking.tariffs_parking_id_name_id_index');
    }
};
