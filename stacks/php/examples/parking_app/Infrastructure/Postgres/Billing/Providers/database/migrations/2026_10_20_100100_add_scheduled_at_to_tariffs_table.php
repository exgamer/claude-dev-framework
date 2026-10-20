<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    public function up(): void
    {
        // Колонка nullable: у существующих тарифов даты активации нет, заполнять нечего. Статус scheduled — значение
        // той же колонки status (varchar(20)), меняется только её комментарий
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->comment('Дата активации запланированного тарифа');
            $table->string('status', 20)->default('active')->comment('Статус: active, scheduled, archived')->change();
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('status', 20)->default('active')->comment('Статус: active, archived')->change();
        });

        Schema::dropColumns($this->tableName, 'scheduled_at');
    }
};
