<?php

declare(strict_types=1);

namespace Tests\Feature\ParkingApp\Admin\Billing;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Repositories\OutboxTariffEventRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Domains\Catalog\Modules\Parkings\Repositories\ParkingRepositoryInterface;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate;
use Mockery\MockInterface;
use MPS\Core\Enums\CurrencyEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\ParkingApp\Admin\Billing\Fixtures\SuperAdminMiddleware;
use Tests\TestCase;

/**
 * Маршруты Admin тарифов: неверный id пути (0, -1, abc) — 422 из Request, а не 404 маршрута;
 * статический tariffs/default объявлен до tariffs/{id} и не перехватывается им.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffRouteTest extends TestCase
{
    private const PARKING_ID = 15;

    private const TARIFF_ID = 42;

    private TariffCrudServiceInterface&MockInterface $service;

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        // Алиасы хоста (bootstrap/app.php приложения): права и скоуп — не предмет теста; auth — withoutMiddleware в setUp
        $router->aliasMiddleware('permission', SuperAdminMiddleware::class);
        $router->aliasMiddleware('resolve.admin.parkings', SuperAdminMiddleware::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // алиас auth синхронизирует HTTP-ядро тестбенча, переопределение в defineRoutes не держится
        $this->withoutMiddleware(Authenticate::class);
        $this->service = $this->mock(TariffCrudServiceInterface::class);
        // сборка контроллера целиком: реализация репозитория парковок — у хоста, вне подпроекта; кеш — без драйвера,
        // конфиг outbox — из .env
        $this->mock(ParkingRepositoryInterface::class);
        $this->mock(TariffCacheRepositoryInterface::class);
        config()->set('parking-app.outbox_tariff_events.max_attempts', 5);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidIdRequests(): array
    {
        $requests = [];

        foreach (['ноль' => '0', 'отрицательный' => '-1', 'не число' => 'abc'] as $name => $id) {
            $requests['GET ' . $name] = ['GET', '/parking/admin/tariffs/' . $id];
            $requests['PUT ' . $name] = ['PUT', '/parking/admin/tariffs/' . $id];
            $requests['set-default ' . $name] = ['POST', '/parking/admin/tariffs/' . $id . '/set-default'];
        }

        return $requests;
    }

    #[DataProvider('invalidIdRequests')]
    public function test_invalid_path_id_is_validation_error(string $method, string $uri): void
    {
        $this->service->shouldNotReceive('findById');
        $this->service->shouldNotReceive('update');

        $this->json($method, $uri)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('id', 'data.details');
    }

    public function test_default_route_is_not_taken_by_id_route(): void
    {
        $tariff = $this->tariff();
        $this->mock(TariffRepositoryInterface::class)
            ->shouldReceive('getDefaultByParkingId')->once()->with(self::PARKING_ID)->andReturn($tariff);
        $this->mock(OutboxTariffEventRepositoryInterface::class)
            ->shouldReceive('getLatestByTariffId')->once()->with(self::TARIFF_ID)->andReturnNull();
        $this->service->shouldNotReceive('findById');

        $this->getJson('/parking/admin/tariffs/default?parking_id=' . self::PARKING_ID)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tariff.id', self::TARIFF_ID)
            ->assertJsonPath('data.last_event_status', null);
    }

    private function tariff(): Tariff
    {
        $tariff = new Tariff();
        $tariff->id = self::TARIFF_ID;
        $tariff->parking_id = self::PARKING_ID;
        $tariff->name = 'Стандарт';
        $tariff->currency = CurrencyEnum::KZT;
        $tariff->price = 50000;
        $tariff->is_default = true;
        $tariff->status = TariffStatusEnum::ACTIVE;
        $tariff->grace_minutes = 15;
        $tariff->created_at = Carbon::now();

        return $tariff;
    }
}
