<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Requests\ParkingTariffs\IndexRequest;
use App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Responses\ParkingTariffsResponse;
use App\ParkingApp\Workflows\Billing\Tariffs\ListParkingTariffsWorkflow;
use Illuminate\Http\JsonResponse;
use MPS\Core\Exceptions\NotFoundAppException;
use OpenApi\Attributes as OA;

/**
 * Действующие тарифы парковки для машинного вызова партнёра.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ParkingTariffController extends Controller
{
    public function __construct(
        private readonly ListParkingTariffsWorkflow $listParkingTariffsWorkflow,
    ) {
    }

    /**
     * @throws NotFoundAppException
     */
    #[OA\Get(
        path: '/parking/integration/parkings/{id}/tariffs',
        summary: 'Действующие тарифы парковки для партнёра',
        description: 'Машинный вызов с HMAC-подписью; подпись не принята или nonce повторён — 401. Первым идёт тариф по умолчанию',
        security: [['Signature' => []]],
        tags: ['Integration Parking: Тарифы'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID парковки', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ParkingTariffsResponse'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/Unauthorized401', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound404', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function index(int $id, IndexRequest $request): JsonResponse
    {
        $parkingTariffs = $this->listParkingTariffsWorkflow->execute($id);

        return response()->json(new ParkingTariffsResponse($parkingTariffs));
    }
}
