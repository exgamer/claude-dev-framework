<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Query;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Страница списка: номер и размер.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class Page
{
    private const DEFAULT_PER_PAGE = 30;

    private function __construct(
        private readonly int $number,
        private readonly int $perPage,
    ) {
    }

    public static function make(int $number, int $perPage): self
    {
        return new self($number, $perPage);
    }

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            (int) $request->validated('page', 1),
            (int) $request->validated('per_page', self::DEFAULT_PER_PAGE),
        );
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }
}
