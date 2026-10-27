<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Validators;

use App\ParkingApp\Common\Enums\TimeUnitEnum;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;

/**
 * Инварианты тарифа на любом пути записи (HTTP, консоль, workflow): непустое название, льготный период до суток, дата активации не в прошлом; ошибки — по полям, 422.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class TariffDtoValidator
{
    /**
     * @return array<string, string[]>
     */
    public static function validate(TariffDto $dto): array
    {
        $errors = [];

        if (trim($dto->getName()) === '') {
            $errors['name'][] = 'Название обязательно';
        }

        if ($dto->getGraceMinutes() !== null && $dto->getGraceMinutes() > TimeUnitEnum::DAY->minutes()) {
            $errors['grace_minutes'][] = 'Льготный период не может превышать сутки';
        }

        if ($dto->getScheduledAt() !== null && $dto->getScheduledAt()->isPast()) {
            $errors['scheduled_at'][] = 'Дата активации не может быть в прошлом';
        }

        return $errors;
    }
}
