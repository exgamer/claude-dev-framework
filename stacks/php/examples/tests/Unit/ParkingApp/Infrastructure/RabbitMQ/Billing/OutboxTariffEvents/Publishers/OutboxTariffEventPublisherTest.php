<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Infrastructure\RabbitMQ\Billing\OutboxTariffEvents\Publishers;

use App\ParkingApp\Core\Exceptions\ServerConfigurationException;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\DTO\OutboxTariffEventDto;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Infrastructure\RabbitMQ\Billing\OutboxTariffEvents\Publishers\OutboxTariffEventPublisher;
use Illuminate\Config\Repository as Config;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Utils\Components\Queue\RabbitMQ\DataObject\MessageConfig;
use MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\QueueManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Публикация события о тарифе: exchange из конфига, event_id в теле и заголовке; брокер не принял — OperationFailedAppException.
 *
 * @author Имя Фамилия <name@example.com>
 */
class OutboxTariffEventPublisherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const EVENT_ID = 77;

    private const EXCHANGE = 'billing.tariff-events';

    private QueueManagerInterface&MockInterface $queueManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueManager = Mockery::mock(QueueManagerInterface::class);
    }

    public function test_event_is_published_with_event_id_in_body_and_header(): void
    {
        $this->queueManager
            ->shouldReceive('putInExchange')
            ->once()
            ->with(
                self::EXCHANGE,
                Mockery::on(fn (array $payload): bool => $payload['event_id'] === self::EVENT_ID
                    && $payload['tariff_id'] === 10
                    && $payload['currency'] === 'KZT'),
                OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED->value,
                Mockery::on(fn (MessageConfig $config): bool => $config->getApplicationHeaders() === ['event_id' => (string) self::EVENT_ID]),
            )
            ->andReturn(true);

        $this->publisher()->publish(self::EVENT_ID, OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED, $this->dto());
    }

    public function test_rejected_by_broker_fails(): void
    {
        $this->queueManager->shouldReceive('putInExchange')->once()->andReturn(false);

        $this->expectException(OperationFailedAppException::class);

        $this->publisher()->publish(self::EVENT_ID, OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED, $this->dto());
    }

    public function test_missing_exchange_is_configuration_error(): void
    {
        $this->expectException(ServerConfigurationException::class);

        new OutboxTariffEventPublisher($this->queueManager, new Config([]));
    }

    private function publisher(): OutboxTariffEventPublisher
    {
        $config = new Config(['app' => ['rabbit-config' => ['billing' => ['tariff-events' => ['exchange' => self::EXCHANGE]]]]]);

        return new OutboxTariffEventPublisher($this->queueManager, $config);
    }

    private function dto(): OutboxTariffEventDto
    {
        return OutboxTariffEventDto::make()
            ->setTariffId(10)
            ->setParkingId(15)
            ->setName('Стандарт')
            ->setCurrency(CurrencyEnum::KZT)
            ->setPrice(50000)
            ->setGraceMinutes(15);
    }
}
