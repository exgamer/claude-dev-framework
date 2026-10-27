<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Queue\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\ParkingDeactivatedEventDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\DeactivateParkingTariffsCommand;
use MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job as UtilsJob;

/**
 * Обработчик handbook.parking.deactivated: парковку отключили в справочнике — её тарифы в архив.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ParkingDeactivatedHandler extends UtilsJob
{
    public function __construct(
        private readonly DeactivateParkingTariffsCommand $deactivateParkingTariffsCommand,
    ) {
    }

    /**
     * Постоянная ошибка (нет parking_id) — повтор не поможет: error-лог и выход без исключения, сообщение
     * подтверждается. Временная (БД недоступна) — исключение Command уходит наверх, ядро повторит сообщение
     * с задержками durability.delayed. Повторная доставка безопасна: архивные тарифы Command не трогает.
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

        $archived = $this->deactivateParkingTariffsCommand->execute($parkingId);

        $this->infoLog("Тарифы парковки {$parkingId} в архиве: {$archived}");
    }

    protected function logId(): string
    {
        return 'BILLING:TARIFFS:PARKING-DEACTIVATED';
    }
}
