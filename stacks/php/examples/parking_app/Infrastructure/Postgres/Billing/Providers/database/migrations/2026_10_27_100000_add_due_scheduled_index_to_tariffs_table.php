<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Частичный индекс под выборку запланированных тарифов с наступившей датой активации (billing:tariffs:activate-due).
 */
return new class () extends Migration {
    public function up(): void
    {
        $scheduled = TariffStatusEnum::SCHEDULED->value;

        DB::statement(
            "CREATE INDEX tariffs_scheduled_at_id_scheduled_index ON parking.tariffs (scheduled_at, id)
            WHERE status = '{$scheduled}' AND deleted_at IS NULL",
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX parking.tariffs_scheduled_at_id_scheduled_index');
    }
};
