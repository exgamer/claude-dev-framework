<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Responses\TariffResponse;
use Illuminate\Http\JsonResponse;
use MPS\Core\Exceptions\NotFoundAppException;
use OpenApi\Attributes as OA;

/**
 * Тарифы для машинного вызова партнёра.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Tag(name: 'Integration Parking: Тарифы', description: 'Тарифы для партнёров, вызов с HMAC-подписью')]
class TariffController extends Controller
{
    public function __construct(
        private readonly TariffCrudServiceInterface $tariffCrudService,
    ) {
    }

    /**
     * @throws NotFoundAppException
     */
    #[OA\Get(
        path: '/parking/integration/tariffs/{id}',
        summary: 'Тариф по ID для партнёра',
        description: 'Машинный вызов с HMAC-подписью; подпись не принята или nonce повторён — 401. Партнёр видит тариф любой парковки',
        security: [['Signature' => []]],
        tags: ['Integration Parking: Тарифы'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID тарифа', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/IntegrationTariffResponse'),
                ])
            ),
            new OA\Response(response: 401, description: 'Подпись недействительна'),
            new OA\Response(ref: '#/components/responses/NotFound404', response: 404),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function view(int $id): JsonResponse
    {
        // Машинный вызов без пользователя: скоупа нет, null передаётся явно — без ограничения по парковкам
        $tariff = $this->tariffCrudService->findById($id, null);

        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return response()->json(new TariffResponse($tariff));
    }
}
