<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * У парковки не больше одного тарифа по умолчанию: частичный уникальный индекс.
 */
return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    private string $indexName = 'tariffs_parking_id_default_unique';

    /**
     * Дубли молча не исправляются: какой тариф оставить по умолчанию — решение владельца данных, снятый флаг откатом не вернуть.
     * Нашлись — миграция падает со списком парковок, данные правятся отдельной миграцией, затем эта применяется повторно.
     *
     * @throws OperationFailedAppException
     */
    public function up(): void
    {
        $parkingIds = DB::table($this->tableName)
            ->select('parking_id')
            ->where('is_default', true)
            ->whereNull('deleted_at')
            ->groupBy('parking_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('parking_id')
            ->limit(100)
            ->pluck('parking_id')
            ->all();

        if ($parkingIds) {
            throw new OperationFailedAppException(sprintf(
                'У парковок больше одного тарифа по умолчанию, индекс не создан: исправить данные отдельной миграцией и повторить (parking_id=%s, первые 100)',
                implode(', ', $parkingIds),
            ));
        }

        DB::statement("CREATE UNIQUE INDEX {$this->indexName} ON {$this->tableName} (parking_id) WHERE is_default = true AND deleted_at IS NULL");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX parking.{$this->indexName}");
    }
};
