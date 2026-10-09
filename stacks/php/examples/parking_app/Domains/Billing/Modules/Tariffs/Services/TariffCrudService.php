<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Services;

use App\ParkingApp\Core\Database\Query\PaginatedQueryHelper;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Repositories\TariffRepositoryInterface;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Validators\TariffDtoValidator;
use App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models\Tariff;
use MPS\Core\Components\CRUD\DataObjects\SearchDataObject;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\ValidationAppException;
use MPS\Core\Services\Service;

/**
 * CRUD-сервис тарифов. Тариф создаётся не дефолтным: дефолт назначает SetDefaultTariffCommand.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class TariffCrudService extends Service implements TariffCrudServiceInterface
{
    public function __construct(
        private readonly TariffRepositoryInterface $repository,
    ) {
    }

    public function findById(int $id): ?Tariff
    {
        /** @var ?Tariff */
        return $this->repository->oneById($id);
    }

    public function search(SearchDataObject $dto): array
    {
        return PaginatedQueryHelper::instance($this->repository)->search($dto);
    }

    /**
     * @throws ValidationAppException
     */
    public function create(TariffDto $dto): Tariff
    {
        $errors = TariffDtoValidator::validate($dto);

        if ($errors) {
            throw new ValidationAppException('VALIDATION ERROR', $errors);
        }

        return $this->repository->createFromDto($dto, TariffStatusEnum::ACTIVE, false);
    }

    /**
     * @throws NotFoundAppException
     * @throws ValidationAppException
     */
    public function update(int $id, TariffDto $dto): Tariff
    {
        /** @var ?Tariff $tariff */
        $tariff = $this->repository->oneById($id);

        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        $errors = TariffDtoValidator::validate($dto);

        if ($errors) {
            throw new ValidationAppException('VALIDATION ERROR', $errors);
        }

        // PUT — полные данные: UpdateRequest требует все поля, DTO пишется целиком (conventions.md, п. 12)
        $this->repository->updateFromDto($tariff, $dto);

        return $tariff;
    }
}
