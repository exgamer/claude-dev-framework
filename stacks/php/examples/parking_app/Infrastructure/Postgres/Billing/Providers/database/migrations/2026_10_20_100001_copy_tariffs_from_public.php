<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * Перенос тарифов из public.tariffs с прежними id; в public они остаются до отдельной чистки.
 */
return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    private string $sourceTableName = 'public.tariffs';

    /**
     * Статус или валюта вне enum — миграция падает: значение решает владелец данных, не перенос.
     *
     * @throws OperationFailedAppException
     */
    public function up(): void
    {
        if (! Schema::hasTable($this->sourceTableName)) {
            return;
        }

        // scheduled не переносится: даты активации в public нет (колонка — миграция 2026_10_20_100100), Job не поставлен
        $statuses = [TariffStatusEnum::ACTIVE->value, TariffStatusEnum::ARCHIVED->value];

        $unknownIds = DB::table($this->sourceTableName)
            ->where(function ($query) use ($statuses) {
                $query->whereNotIn('status', $statuses)
                    ->orWhereNotIn('currency', CurrencyEnum::values());
            })
            ->orderBy('id')
            ->limit(100)
            ->pluck('id')
            ->all();

        if ($unknownIds) {
            throw new OperationFailedAppException(sprintf(
                'В public.tariffs статус или валюта вне enum, тарифы не перенесены: исправить данные и повторить (id=%s, первые 100)',
                implode(', ', $unknownIds),
            ));
        }

        // ON CONFLICT только по id: дубль тарифа по умолчанию нарушит уникальный индекс и остановит перенос
        DB::statement("
            INSERT INTO {$this->tableName}
                (id, parking_id, name, currency, price, is_default, status, grace_minutes, created_at, updated_at, deleted_at)
            SELECT id, parking_id, name, currency, price, is_default, status, grace_minutes, created_at, updated_at, deleted_at
            FROM {$this->sourceTableName}
            ON CONFLICT (id) DO NOTHING
        ");

        // id вставлены явно, последовательность их не видела: новый тариф получил бы занятый id
        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('{$this->tableName}', 'id'),
                COALESCE((SELECT MAX(id) FROM {$this->tableName}), 0) + 1,
                false
            )
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->sourceTableName)) {
            return;
        }

        // Перенесённая строка — тот же id и тот же created_at, что в public; строка, бывшая в parking до переноса (её пропустил ON CONFLICT), не совпадёт по created_at
        DB::statement("
            DELETE FROM {$this->tableName} AS target
            USING {$this->sourceTableName} AS source
            WHERE target.id = source.id
                AND target.created_at IS NOT DISTINCT FROM source.created_at
        ");
    }
};
