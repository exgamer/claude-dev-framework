<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Database\Query;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Номер и размер страницы из запроса: page 0 или не передан — первая, per_page не передан — 30.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class Page
{
    public const DEFAULT_PER_PAGE = 30;

    public const MAX_PER_PAGE = 100;

    /** Предел номера страницы: глубже — OFFSET читает и отбрасывает слишком много строк */
    public const MAX_PAGE = 10000;

    private function __construct(
        private readonly int $number,
        private readonly int $perPage,
    ) {
    }

    public static function make(int $number, int $perPage): self
    {
        return new self($number, $perPage);
    }

    /**
     * page не передан или 0 — первая страница, как в Go SDK.
     */
    public static function fromRequest(FormRequest $request): self
    {
        $number = (int) $request->validated('page', 1);

        return new self(
            max($number, 1),
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
