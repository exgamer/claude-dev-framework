<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\RabbitMQ\Billing\OutboxTariffEvents\Publishers;

use App\ParkingApp\Core\Exceptions\ServerConfigurationException;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;
use Illuminate\Contracts\Config\Repository as Config;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Queue\RabbitMQ\DataObject\MessageConfig;
use MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\QueueManagerInterface;

/**
 * Публикация событий о тарифах в topic exchange биллинга; routing key — тип события.
 *
 * @author Имя Фамилия <name@example.com>
 */
class OutboxTariffEventPublisher implements OutboxTariffEventPublisherInterface
{
    private readonly string $exchange;

    /**
     * @throws ServerConfigurationException
     */
    public function __construct(
        private readonly QueueManagerInterface $queueManager,
        Config $config,
    ) {
        $exchange = $config->get('app.rabbit-config.billing.tariff-events.exchange');

        if (! is_string($exchange) || $exchange === '') {
            throw new ServerConfigurationException('Не задан exchange событий тарифов: app.rabbit-config.billing.tariff-events.exchange');
        }

        $this->exchange = $exchange;
    }

    /**
     * Ядро ловит любую ошибку соединения и канала и возвращает false — исключений AMQP наверх не бывает.
     * true — сообщение передано в канал (basic_publish), подтверждения брокера ядро не ждёт.
     *
     * @throws OperationFailedAppException
     */
    public function publish(int $eventId, OutboxTariffEventRoutingKeyEnum $routingKey, OutboxTariffEventDto $dto): void
    {
        // event_id — и в теле, и в заголовке: получатель отсекает повтор, не разбирая тело
        $messageConfig = MessageConfig::make()
            ->setPersistent(true)
            ->setApplicationHeaders(['event_id' => (string) $eventId]);

        $published = $this->queueManager->putInExchange(
            exchangeName: $this->exchange,
            payload: [
                'event_id' => $eventId,
                'tariff_id' => $dto->getTariffId(),
                'parking_id' => $dto->getParkingId(),
                'name' => $dto->getName(),
                'currency' => $dto->getCurrency()->value,
                'price' => $dto->getPrice(),
                'grace_minutes' => $dto->getGraceMinutes(),
            ],
            routingKey: $routingKey->value,
            messageConfig: $messageConfig,
        );

        if (! $published) {
            throw new OperationFailedAppException('RabbitMQ не принял событие о тарифе', [
                'outbox_id' => $eventId,
                'routing_key' => $routingKey->value,
            ]);
        }
    }
}
