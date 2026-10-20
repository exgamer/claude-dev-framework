<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Core\Requests;

use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\ViewRequest;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * id пути проверяется rules() запроса: 0, отрицательный, не число и больше предела bigint — 422, а не 404 маршрута или 500; query не подменяет id пути.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class RequestTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $router->middleware('api.response')
            ->get('test/tariffs/{id}', static function (int $id, ViewRequest $request): JsonResponse {
                $validatedId = $request->validated('id');

                return response()->json(['id' => $validatedId]);
            });
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIds(): array
    {
        return [
            'ноль' => ['0'],
            'отрицательный' => ['-1'],
            'не число' => ['abc'],
            'дробный' => ['1.5'],
            'больше bigint' => ['9223372036854775808'],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_invalid_path_id_is_validation_error(string $id): void
    {
        $this->getJson('test/tariffs/' . $id)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('id', 'data.details');
    }

    public function test_path_id_is_validated_and_query_does_not_override_it(): void
    {
        $this->getJson('test/tariffs/42?id=0')
            ->assertOk()
            ->assertJsonPath('data.id', '42');
    }
}
