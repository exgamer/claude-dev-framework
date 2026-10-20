<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Queue\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\ParkingDeactivatedEventDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job as UtilsJob;

/**
 * Обработчик handbook.parking.deactivated: парковку отключили в справочнике — её тарифы в архив.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ParkingDeactivatedHandler extends UtilsJob
{
    public function __construct(
        private readonly TariffCrudServiceInterface $tariffCrudService,
    ) {
    }

    /**
     * Постоянная ошибка (нет parking_id) — повтор не поможет: error-лог и выход без исключения, сообщение
     * подтверждается. Временная (БД недоступна) — исключение сервиса уходит наверх, ядро повторит сообщение
     * с задержками durability.delayed. Повторная доставка безопасна: архивные тарифы сервис не трогает.
     * Своей записи о сообщении нет — постоянная ошибка фиксируется только в логе.
     */
    protected function process(object|array $payload): void
    {
        $dto = ParkingDeactivatedEventDto::make()->fromArray((array) $payload);
        $parkingId = $dto->getParkingId();

        if ($parkingId === null || $parkingId <= 0) {
            $this->errorLog('В сообщении нет parking_id, тарифы не отключены', (array) $payload);

            return;
        }

        $archived = $this->tariffCrudService->deactivateByParkingId($parkingId);

        $this->infoLog("Тарифы парковки {$parkingId} в архиве: {$archived}");
    }

    protected function logId(): string
    {
        return 'BILLING:TARIFFS:PARKING-DEACTIVATED';
    }
}
