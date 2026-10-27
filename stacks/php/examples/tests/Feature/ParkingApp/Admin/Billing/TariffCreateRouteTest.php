<?php

declare(strict_types=1);

namespace Tests\Feature\ParkingApp\Admin\Billing;

use App\ParkingApp\Core\Database\Managers\NoopTransactionManager;
use App\ParkingApp\Core\Database\Managers\TransactionManagerInterface;
use App\ParkingApp\Domains\Billing\Common\Enums\OutboxTariffEventStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventPublisherInterface;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\PriceLimitRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\ParkingRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models\OutboxTariffEvent;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use App\ParkingApp\Workflows\Billing\Tariffs\TariffNotifierInterface;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate;
use Mockery;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Models\Model;
use Tests\Feature\ParkingApp\Admin\Billing\Fixtures\SuperAdminMiddleware;
use Tests\TestCase;

/**
 * POST tariffs целиком — контроллер, workflow, сервис — на мок-репозиториях: ответ 201 строится по перечитанной записи,
 * а не по модели после save(), где колонок с DEFAULT БД (created_at) нет.
 *
 * @author Имя Фамилия <name@example.com>
 */
class TariffCreateRouteTest extends TestCase
{
    private const PARKING_ID = 15;

    private const TARIFF_ID = 42;

    private const OUTBOX_ID = 77;

    private const CREATED_AT = '2026-10-20 09:30:00';

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $router->aliasMiddleware('permission', SuperAdminMiddleware::class);
        $router->aliasMiddleware('resolve.admin.parkings', SuperAdminMiddleware::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Authenticate::class);
        $this->app->instance(TransactionManagerInterface::class, new NoopTransactionManager());
        $this->mock(TariffCacheRepositoryInterface::class);
        $this->mock(PriceLimitRepositoryInterface::class)->shouldReceive('getMaxPrice')->andReturnNull();
        $this->mock(ParkingRepositoryInterface::class)
            ->shouldReceive('oneById')
            ->with(self::PARKING_ID)
            ->andReturn(Mockery::mock(Model::class));
        $this->mock(TariffNotifierInterface::class)->shouldReceive('sendCreated')->once();
        $this->mock(OutboxTariffEventPublisherInterface::class)->shouldReceive('publish')->once();
        $event = $this->outboxEvent();
        $outboxRepository = $this->mock(OutboxTariffEventRepositoryInterface::class);
        $outboxRepository->shouldReceive('createPending')->once()->andReturn($event);
        $outboxRepository->shouldReceive('oneById')->with(self::OUTBOX_ID)->andReturn($event);
        $outboxRepository->shouldReceive('markPublished')->once()->with(self::OUTBOX_ID)->andReturn(true);
        config()->set('parking-app.outbox_tariff_events.max_attempts', 5);
    }

    public function test_create_responds_with_reread_tariff(): void
    {
        $repository = $this->mock(TariffRepositoryInterface::class);
        // Как после save(): created_at ставит DEFAULT БД, в модели его нет
        $repository->shouldReceive('createFromDto')->once()->andReturn($this->tariff(createdAt: null));
        $repository
            ->shouldReceive('getByIdInParkings')
            ->once()
            ->with(self::TARIFF_ID, null)
            ->andReturn($this->tariff(createdAt: Carbon::parse(self::CREATED_AT)));

        $this->postJson('/parking/admin/tariffs', [
            'parking_id' => self::PARKING_ID,
            'name' => 'Стандарт',
            'price' => 50000,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', self::TARIFF_ID)
            ->assertJsonPath('data.status', TariffStatusEnum::ACTIVE->value)
            ->assertJsonPath('data.created_at', Carbon::parse(self::CREATED_AT)->toJSON());
    }

    private function tariff(?Carbon $createdAt): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = self::TARIFF_ID;
        $tariff->parking_id = self::PARKING_ID;
        $tariff->name = 'Стандарт';
        $tariff->currency = CurrencyEnum::KZT;
        $tariff->price = 50000;
        $tariff->grace_minutes = null;
        $tariff->initState(null);

        if ($createdAt !== null) {
            $tariff->created_at = $createdAt;
        }

        return $tariff;
    }

    private function outboxEvent(): OutboxTariffEvent
    {
        $event = new OutboxTariffEvent();
        $event->id = self::OUTBOX_ID;
        $event->tariff_id = self::TARIFF_ID;
        $event->parking_id = self::PARKING_ID;
        $event->name = 'Стандарт';
        $event->currency = CurrencyEnum::KZT;
        $event->price = 50000;
        $event->grace_minutes = null;
        $event->routing_key = OutboxTariffEventRoutingKeyEnum::TARIFF_CREATED;
        $event->status = OutboxTariffEventStatusEnum::PENDING;

        return $event;
    }
}
