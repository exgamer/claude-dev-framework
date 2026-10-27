<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Схема parking подпроекта.
 */
return new class () extends Migration {
    private string $schemaName = 'parking';

    public function up(): void
    {
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$this->schemaName}");
    }

    public function down(): void
    {
        // Без CASCADE: откат не удаляет таблицы с данными, непустая схема — ошибка
        DB::statement("DROP SCHEMA IF EXISTS {$this->schemaName}");
    }
};
