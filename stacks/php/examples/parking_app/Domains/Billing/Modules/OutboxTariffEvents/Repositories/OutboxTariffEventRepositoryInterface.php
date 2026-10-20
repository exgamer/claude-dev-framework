<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface;

/**
 * Интерфейс репозитория outbox событий о тарифах. Владельца у события нет — поиск по ID — `oneById` ядра.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface OutboxTariffEventRepositoryInterface extends CRUDRepositoryInterface
{
    /**
     * Последнее событие тарифа по ID; событий нет — null.
     */
    public function getLatestByTariffId(int $tariffId): ?OutboxTariffEvent;

    /**
     * Новое событие в статусе PENDING, попыток 0. Вызывается в транзакции сценария, который пишет тариф.
     */
    public function createPending(OutboxTariffEventDto $dto, OutboxTariffEventRoutingKeyEnum $routingKey): OutboxTariffEvent;

    /**
     * Только из PENDING/AWAITING_RETRY: повтор не перезаписывает итог. false — событие уже не ждёт отправки.
     */
    public function markPublished(int $id): bool;

    /**
     * Только из PENDING/AWAITING_RETRY: опоздавшая неудача не затирает PUBLISHED.
     */
    public function markFailed(int $id): void;

    /**
     * Только из PENDING/AWAITING_RETRY, как markFailed.
     */
    public function markAwaitingRetry(int $id): void;

    /**
     * Число неудачных попыток после увеличения; 0 — события нет.
     */
    public function incrementAttempts(int $id): int;

    /**
     * ID до $limit зависших событий (PENDING/AWAITING_RETRY, не менялись дольше $staleAfterSeconds) по возрастанию ID.
     * Взятые сдвигают updated_at — параллельный прогон их не возьмёт; строки, заблокированные другим прогоном,
     * пропускаются (FOR UPDATE SKIP LOCKED).
     *
     * @return list<int>
     */
    public function claimForRetry(int $limit, int $staleAfterSeconds): array;
}
