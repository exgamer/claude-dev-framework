<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Entrypoints\Queue\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Entrypoints\Queue\Billing\Tariffs\ParkingDeactivatedHandler;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use MPS\Core\Exceptions\OperationFailedAppException;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Обработчик handbook.parking.deactivated: архив тарифов, постоянная ошибка — лог, временная — наверх; Tests\TestCase — ради фасада Log.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ParkingDeactivatedHandlerTest extends TestCase
{
    private const PARKING_ID = 15;

    private TariffCrudServiceInterface&MockInterface $tariffCrudService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tariffCrudService = Mockery::mock(TariffCrudServiceInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_tariffs_of_deactivated_parking_are_archived(): void
    {
        $this->tariffCrudService
            ->shouldReceive('deactivateByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn(2);
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->never();

        $this->process(['parking_id' => self::PARKING_ID]);
    }

    public function test_object_payload_is_accepted(): void
    {
        $this->tariffCrudService
            ->shouldReceive('deactivateByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn(0);
        Log::shouldReceive('info')->once();

        $this->process((object) ['parking_id' => self::PARKING_ID]);
    }

    public function test_message_without_parking_id_is_rejected_without_retry(): void
    {
        $this->tariffCrudService->shouldNotReceive('deactivateByParkingId');
        Log::shouldReceive('error')->twice();

        // Ни исключения, ни вызова сервиса: повтор такого сообщения бесполезен
        $this->process(['id' => self::PARKING_ID]);
        $this->process(['parking_id' => 0]);
    }

    public function test_transient_failure_is_rethrown_for_retry(): void
    {
        $this->tariffCrudService
            ->shouldReceive('deactivateByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andThrow(new OperationFailedAppException('Postgres недоступен'));
        Log::shouldReceive('error')->never();

        $this->expectException(OperationFailedAppException::class);

        $this->process(['parking_id' => self::PARKING_ID]);
    }

    private function process(object|array $payload): void
    {
        $handler = new ParkingDeactivatedHandler($this->tariffCrudService);

        // process() — protected-хук ядра: так его вызывает UtilsJob::handle()
        (new ReflectionMethod($handler, 'process'))->invoke($handler, $payload);
    }
}
