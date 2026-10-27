<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs;

use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\IndexRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Границы списка: page 0 — первая страница, глубже 10000 — 422; parking_id — положительный в пределах bigint; per_page ≤ 100.
 *
 * @author Имя Фамилия <name@example.com>
 */
class IndexRequestTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function validQueries(): array
    {
        return [
            'пусто' => [[]],
            'page 0 — первая' => [['page' => 0]],
            'page на пределе' => [['page' => 10000, 'per_page' => 100]],
            'parking_id на пределе bigint' => [['parking_id' => '9223372036854775807']],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('validQueries')]
    public function test_valid_query_passes(array $query): void
    {
        $validator = Validator::make($query, (new IndexRequest())->rules());

        $this->assertTrue($validator->passes(), (string) json_encode($validator->errors()->toArray()));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidQueries(): array
    {
        return [
            'page отрицательный' => [['page' => -1], 'page'],
            'page глубже 10000' => [['page' => 10001], 'page'],
            'per_page больше 100' => [['per_page' => 101], 'per_page'],
            'parking_id 0' => [['parking_id' => 0], 'parking_id'],
            'parking_id больше bigint' => [['parking_id' => '9223372036854775808'], 'parking_id'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('invalidQueries')]
    public function test_invalid_query_fails_on_field(array $query, string $field): void
    {
        $validator = Validator::make($query, (new IndexRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey($field, $validator->errors()->toArray());
    }
}
