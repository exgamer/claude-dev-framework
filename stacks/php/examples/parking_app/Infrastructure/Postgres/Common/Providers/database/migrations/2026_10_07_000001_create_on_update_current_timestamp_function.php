<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Функция триггера updated_at для таблиц схемы parking: подпроект не зависит от функции в public приложения.
 */
return new class () extends Migration {
    private string $functionName = 'parking.on_update_current_timestamp';

    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION {$this->functionName}()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP FUNCTION IF EXISTS {$this->functionName}()");
    }
};
