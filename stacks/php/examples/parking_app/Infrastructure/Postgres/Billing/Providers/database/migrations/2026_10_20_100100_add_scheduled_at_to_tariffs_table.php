<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * Дата активации запланированного тарифа и статус scheduled.
 */
return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    public function up(): void
    {
        // Колонка nullable: у существующих тарифов даты активации нет, заполнять нечего. Статус scheduled — значение
        // той же колонки status (varchar(20)), меняется только её комментарий
        $statuses = implode(', ', TariffStatusEnum::values());

        Schema::table($this->tableName, function (Blueprint $table) use ($statuses) {
            $table->timestamp('scheduled_at')->nullable()->comment('Дата активации запланированного тарифа');
            $table->string('status', 20)
                ->default(TariffStatusEnum::ACTIVE->value)
                ->comment('Статус: ' . $statuses)
                ->change();
        });
    }

    /**
     * Запланированный тариф без scheduled_at не активировать: есть такие строки — откат отказывает, данные не правятся молча
     * (сначала — отдельной миграцией данных или решением, что с ними делать).
     *
     * @throws OperationFailedAppException
     */
    public function down(): void
    {
        $scheduledCount = DB::table($this->tableName)
            ->where('status', TariffStatusEnum::SCHEDULED->value)
            ->count();

        if ($scheduledCount > 0) {
            throw new OperationFailedAppException('Откат невозможен: есть запланированные тарифы', ['count' => $scheduledCount]);
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('status', 20)
                ->default(TariffStatusEnum::ACTIVE->value)
                ->comment('Статус: ' . TariffStatusEnum::ACTIVE->value . ', ' . TariffStatusEnum::ARCHIVED->value)
                ->change();
        });

        Schema::dropColumns($this->tableName, 'scheduled_at');
    }
};
