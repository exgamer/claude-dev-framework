<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Requests\Tariffs\ViewRequest;
use App\ParkingApp\Entrypoints\Integration\Billing\Tariffs\Http\Responses\TariffResponse;
use Illuminate\Http\JsonResponse;
use MPS\Core\Exceptions\NotFoundAppException;
use OpenApi\Attributes as OA;

/**
 * Чтение тарифа партнёром по подписи запроса: без скоупа администратора, ответ без служебных полей.
 *
 * @author Имя Фамилия <name@example.com>
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
            new OA\Parameter(name: 'id', description: 'ID тарифа', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
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
            new OA\Response(ref: '#/components/responses/Unauthorized401', response: 401),
            new OA\Response(ref: '#/components/responses/NotFound404', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function view(int $id, ViewRequest $request): JsonResponse
    {
        // Машинный вызов без пользователя: скоупа нет, null передаётся явно — без ограничения по парковкам
        $tariff = $this->tariffCrudService->findById($id, null);

        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return response()->json(new TariffResponse($tariff));
    }
}
