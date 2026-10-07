<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('parking.tariffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parking_id')->comment('ID парковки');
            $table->string('name')->comment('Название тарифа');
            $table->string('currency', 3)->default('KZT')->comment('Валюта');
            $table->boolean('is_default')->default(false)->comment('Тариф по умолчанию');
            $table->string('status', 20)->default('active')->comment('Статус: active, archived');
            $table->unsignedSmallInteger('grace_minutes')->nullable()->comment('Льготный период (минуты)');
            $table->timestamps();
            $table->softDeletes();

            $table->index('parking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking.tariffs');
    }
};
