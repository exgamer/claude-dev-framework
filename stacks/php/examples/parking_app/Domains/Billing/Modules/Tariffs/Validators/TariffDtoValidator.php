<?php

declare(strict_types=1);

namespace App\ParkingApp\Domains\Billing\Modules\Tariffs\Validators;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;

/**
 * Доменные инварианты тарифа (форма запроса проверяется в FormRequest).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
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
            self::add($errors, 'name', 'Название обязательно');
        }

        if ($dto->getGraceMinutes() !== null && $dto->getGraceMinutes() > 1440) {
            self::add($errors, 'grace_minutes', 'Льготный период не может превышать сутки');
        }

        return $errors;
    }

    private static function add(array &$errors, string $field, string $message): void
    {
        $errors[$field][] = $message;
    }
}
