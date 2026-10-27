<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Entrypoints\Queue\Billing\Tariffs;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\DeactivateParkingTariffsCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffCacheRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
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
 * @author Имя Фамилия <name@example.com>
 */
class ParkingDeactivatedHandlerTest extends TestCase
{
    private const PARKING_ID = 15;

    private TariffRepositoryInterface&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Command — final: настоящий, на мок-репозитории; изменённых ID нет — кеш не сбрасывается
        $this->repository = Mockery::mock(TariffRepositoryInterface::class);
    }

    public function test_tariffs_of_deactivated_parking_are_archived(): void
    {
        $this->repository
            ->shouldReceive('archiveNotArchivedByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn([]);
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->never();

        $this->process(['parking_id' => self::PARKING_ID]);
    }

    public function test_object_payload_is_accepted(): void
    {
        $this->repository
            ->shouldReceive('archiveNotArchivedByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andReturn([]);
        Log::shouldReceive('info')->once();

        $this->process((object) ['parking_id' => self::PARKING_ID]);
    }

    public function test_message_without_parking_id_is_rejected_without_retry(): void
    {
        $this->repository->shouldNotReceive('archiveNotArchivedByParkingId');
        Log::shouldReceive('error')->twice();

        // Ни исключения, ни вызова Command: повтор такого сообщения бесполезен
        $this->process(['id' => self::PARKING_ID]);
        $this->process(['parking_id' => 0]);
    }

    public function test_transient_failure_is_rethrown_for_retry(): void
    {
        $this->repository
            ->shouldReceive('archiveNotArchivedByParkingId')
            ->once()
            ->with(self::PARKING_ID)
            ->andThrow(new OperationFailedAppException('Postgres недоступен'));
        Log::shouldReceive('error')->never();

        $this->expectException(OperationFailedAppException::class);

        $this->process(['parking_id' => self::PARKING_ID]);
    }

    private function process(object|array $payload): void
    {
        $command = new DeactivateParkingTariffsCommand(
            $this->repository,
            Mockery::mock(TariffCacheRepositoryInterface::class),
            Mockery::mock(ExceptionHandler::class),
        );
        $handler = new ParkingDeactivatedHandler($command);

        // process() — protected-хук ядра: так его вызывает UtilsJob::handle()
        $process = new ReflectionMethod($handler, 'process');
        $process->invoke($handler, $payload);
    }
}
