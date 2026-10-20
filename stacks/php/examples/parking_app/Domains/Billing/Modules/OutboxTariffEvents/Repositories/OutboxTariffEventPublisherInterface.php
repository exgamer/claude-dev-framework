<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;

/**
 * Порт публикации события о тарифе в брокер.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
interface OutboxTariffEventPublisherInterface
{
    /**
     * $eventId уходит в сообщение как event_id — по нему получатель отсекает повторную доставку.
     *
     * @throws OperationFailedAppException брокер не принял событие — временная ошибка, событие повторят
     */
    public function publish(int $eventId, OutboxTariffEventRoutingKeyEnum $routingKey, OutboxTariffEventDto $dto): void;
}
