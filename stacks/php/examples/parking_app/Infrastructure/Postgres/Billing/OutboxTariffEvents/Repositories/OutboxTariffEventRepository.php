<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Repositories;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use Carbon\Carbon;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepository;
use MPS\Core\Exceptions\InvalidConfigurationAppException;

/**
 * Репозиторий outbox событий о тарифах
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class OutboxTariffEventRepository extends CRUDRepository implements OutboxTariffEventRepositoryInterface
{
    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(OutboxTariffEvent $model)
    {
        $this->model = $model;

        parent::__construct();
    }

    public function getLatestByTariffId(int $tariffId): ?OutboxTariffEvent
    {
        /** @var ?OutboxTariffEvent */
        return $this->getQuery()
            ->where('tariff_id', $tariffId)
            ->orderByDesc('id')
            ->first();
    }

    public function createPending(OutboxTariffEventDto $dto, OutboxTariffEventRoutingKeyEnum $routingKey): OutboxTariffEvent
    {
        /** @var OutboxTariffEvent $event */
        $event = $this->model->newInstance();
        $event->fill([
            'tariff_id' => $dto->getTariffId(),
            'parking_id' => $dto->getParkingId(),
            'name' => $dto->getName(),
            'currency' => $dto->getCurrency(),
            'price' => $dto->getPrice(),
            'grace_minutes' => $dto->getGraceMinutes(),
            'routing_key' => $routingKey,
            'status' => OutboxTariffEventStatusEnum::PENDING,
            'attempts' => 0,
        ])->save();

        return $event;
    }

    public function markPublished(int $id): bool
    {
        $updated = $this->getQuery()
            ->whereKey($id)
            ->whereIn('status', OutboxTariffEventStatusEnum::deliverableValues())
            ->update([
                'status' => OutboxTariffEventStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now(),
            ]);

        return $updated > 0;
    }

    public function markFailed(int $id): void
    {
        $this->getQuery()
            ->whereKey($id)
            ->whereIn('status', OutboxTariffEventStatusEnum::deliverableValues())
            ->update(['status' => OutboxTariffEventStatusEnum::FAILED->value]);
    }

    public function markAwaitingRetry(int $id): void
    {
        $this->getQuery()
            ->whereKey($id)
            ->whereIn('status', OutboxTariffEventStatusEnum::deliverableValues())
            ->update(['status' => OutboxTariffEventStatusEnum::AWAITING_RETRY->value]);
    }

    public function incrementAttempts(int $id): int
    {
        // RETURNING: новое значение — тем же запросом, без чтения после записи
        $row = $this->model->getConnection()->selectOne(
            "UPDATE {$this->model->getTable()} SET attempts = attempts + 1 WHERE id = ? RETURNING attempts",
            [$id],
        );

        return $row === null ? 0 : (int) $row->attempts;
    }

    public function claimForRetry(int $limit, int $staleAfterSeconds): array
    {
        // Один UPDATE: подзапрос FOR UPDATE SKIP LOCKED выбирает зависшие строки, UPDATE сдвигает updated_at — их не
        // возьмёт параллельный прогон, RETURNING отдаёт ID. Блокировка живёт только в этом запросе, публикация — без
        // транзакции. Новое событие ещё не обновлялось (updated_at NULL) — возраст по created_at
        $table = $this->model->getTable();
        [$pending, $awaitingRetry] = OutboxTariffEventStatusEnum::deliverableValues();

        $rows = $this->model->getConnection()->select(
            "UPDATE {$table} SET updated_at = CURRENT_TIMESTAMP
            WHERE id IN (
                SELECT id FROM {$table}
                WHERE status IN (?, ?)
                    AND COALESCE(updated_at, created_at) < CURRENT_TIMESTAMP - make_interval(secs => ?)
                ORDER BY id
                LIMIT ?
                FOR UPDATE SKIP LOCKED
            )
            RETURNING id",
            [$pending, $awaitingRetry, $staleAfterSeconds, $limit],
        );

        $ids = array_map(static fn (object $row): int => (int) $row->id, $rows);
        sort($ids);

        return $ids;
    }
}
