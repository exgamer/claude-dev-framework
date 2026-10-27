<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Repositories;

use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Repositories\TariffRepository;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PDOException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Переходы — условный UPDATE (0 строк — false); уникальность тарифа по умолчанию: нарушение именно этого индекса
 * (SQLSTATE 23505 + имя) — false, другое — наверх.
 *
 * @author Имя Фамилия <name@example.com>
 */
class TariffRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TARIFF_ID = 42;

    private TariffRepository&MockInterface $repository;

    private Builder&MockInterface $query;

    protected function setUp(): void
    {
        parent::setUp();

        // Запрос — мок Builder: БД в unit-тесте нет, проверяется разбор QueryException
        $this->query = Mockery::mock(Builder::class);
        $this->query->shouldReceive('whereKey')->with(self::TARIFF_ID)->andReturnSelf();
        $this->query->shouldReceive('where')->andReturnSelf();
        $this->repository = Mockery::mock(TariffRepository::class)->makePartial();
        $this->repository->shouldReceive('getQuery')->andReturn($this->query);
    }

    public function test_default_tariff_index_violation_is_false(): void
    {
        $exception = $this->queryException('23505', 'duplicate key value violates unique constraint "tariffs_parking_id_default_unique"');
        $this->query->shouldReceive('update')->once()->andThrow($exception);

        $this->assertFalse($this->repository->setDefault(self::TARIFF_ID));
    }

    public function test_other_unique_index_violation_is_rethrown(): void
    {
        $exception = $this->queryException('23505', 'duplicate key value violates unique constraint "tariffs_pkey"');
        $this->query->shouldReceive('update')->once()->andThrow($exception);

        $this->expectExceptionObject($exception);

        $this->repository->setDefault(self::TARIFF_ID);
    }

    public function test_other_error_with_index_name_is_rethrown(): void
    {
        $exception = $this->queryException('40001', 'could not serialize access, index "tariffs_parking_id_default_unique"');
        $this->query->shouldReceive('update')->once()->andThrow($exception);

        $this->expectExceptionObject($exception);

        $this->repository->setDefault(self::TARIFF_ID);
    }

    public function test_successful_update_is_true(): void
    {
        $this->query->shouldReceive('update')->once()->with(['is_default' => true])->andReturn(1);

        $this->assertTrue($this->repository->setDefault(self::TARIFF_ID));
    }

    public function test_set_default_updates_only_active_not_default_tariff(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereKey')->once()->with(self::TARIFF_ID)->andReturnSelf();
        $query->shouldReceive('where')->once()->with('status', 'active')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('is_default', false)->andReturnSelf();
        // 0 строк: тариф архивирован или назначен параллельно — переход не сделан
        $query->shouldReceive('update')->once()->with(['is_default' => true])->andReturn(0);
        $repository = Mockery::mock(TariffRepository::class)->makePartial();
        $repository->shouldReceive('getQuery')->andReturn($query);

        $this->assertFalse($repository->setDefault(self::TARIFF_ID));
    }

    public function test_archive_updates_only_not_archived_tariff(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereKey')->once()->with(self::TARIFF_ID)->andReturnSelf();
        $query->shouldReceive('whereIn')->once()->with('status', ['active', 'scheduled'])->andReturnSelf();
        $query->shouldReceive('update')->once()->with(['status' => 'archived', 'is_default' => false])->andReturn(0);
        $repository = Mockery::mock(TariffRepository::class)->makePartial();
        $repository->shouldReceive('getQuery')->andReturn($query);

        $this->assertFalse($repository->archive(self::TARIFF_ID));
    }

    private function queryException(string $sqlState, string $message): QueryException
    {
        // У PDOException код — строка SQLSTATE, конструктор Exception принимает только int
        $previous = new PDOException("SQLSTATE[{$sqlState}]: {$message}");
        $code = new ReflectionProperty(Exception::class, 'code');
        $code->setValue($previous, $sqlState);

        return new QueryException('pgsql', 'update "parking"."tariffs" set "is_default" = ? where "id" = ?', [true, self::TARIFF_ID], $previous);
    }
}
