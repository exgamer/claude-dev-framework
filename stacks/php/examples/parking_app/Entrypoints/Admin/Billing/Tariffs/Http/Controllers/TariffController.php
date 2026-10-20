<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\ParkingApp\Core\Database\Query\Page;
use App\ParkingApp\Core\Http\RequestHelper;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\SetDefaultTariffCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffFilterDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\CreateRequest;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\IndexRequest;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\UpdateRequest;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses\TariffPaginatedResponse;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses\TariffResponse;
use App\ParkingApp\Workflows\Billing\Tariffs\CreateTariffWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\OperationFailedAppException;
use MPS\Core\Exceptions\ValidationAppException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Тарифы парковок в админке.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
#[OA\Tag(name: 'Admin Parking: Тарифы', description: 'Управление тарифами парковок')]
class TariffController extends Controller
{
    public function __construct(
        private readonly TariffCrudServiceInterface $tariffCrudService,
        private readonly CreateTariffWorkflow $createTariffWorkflow,
        private readonly SetDefaultTariffCommand $setDefaultTariffCommand,
    ) {
    }

    /**
     * @throws InvalidConfigurationAppException
     */
    #[OA\Get(
        path: '/parking/admin/tariffs',
        summary: 'Список тарифов',
        description: 'Только тарифы парковок, доступных администратору; parking_id недоступной парковки — 403',
        security: [['Authorization' => []]],
        tags: ['Admin Parking: Тарифы'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestPage'),
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestPerPage'),
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestParkingId'),
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestStatus'),
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestSort'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffPaginatedResponse'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/AccessDenied403', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function index(IndexRequest $request): JsonResponse
    {
        $filter = TariffFilterDto::make()
            ->fromArray($request->validated())
            ->setParkingIds(RequestHelper::getParkingIds($request));
        $tariffs = $this->tariffCrudService->paginate($filter, Page::fromRequest($request));

        return response()->json(new TariffPaginatedResponse($tariffs));
    }

    /**
     * @throws NotFoundAppException
     * @throws InvalidConfigurationAppException
     */
    #[OA\Get(
        path: '/parking/admin/tariffs/{id}',
        summary: 'Тариф по ID',
        description: 'Тариф парковки, недоступной администратору, — 404',
        security: [['Authorization' => []]],
        tags: ['Admin Parking: Тарифы'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID тарифа', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/NotFound404', response: 404),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function view(int $id, Request $request): JsonResponse
    {
        $tariff = $this->tariffCrudService->findById($id, RequestHelper::getParkingIds($request));

        if (! $tariff) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return response()->json(new TariffResponse($tariff));
    }

    /**
     * @throws ValidationAppException
     * @throws OperationFailedAppException
     * @throws Throwable
     */
    #[OA\Post(
        path: '/parking/admin/tariffs',
        summary: 'Создать тариф',
        description: 'Цена проверяется по лимиту городского реестра парковок; о новом тарифе уведомляется партнёрский биллинг; parking_id недоступной парковки — 403',
        security: [['Authorization' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TariffCreateRequest')),
        tags: ['Admin Parking: Тарифы'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Тариф создан',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/AccessDenied403', response: 403),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function create(CreateRequest $request): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());
        $tariff = $this->createTariffWorkflow->execute($dto);

        return response()->json(new TariffResponse($tariff), Response::HTTP_CREATED);
    }

    /**
     * @throws NotFoundAppException
     * @throws ValidationAppException
     * @throws InvalidConfigurationAppException
     */
    #[OA\Put(
        path: '/parking/admin/tariffs/{id}',
        summary: 'Изменить тариф',
        description: 'Тариф парковки, недоступной администратору, — 404; parking_id недоступной парковки в теле — 403',
        security: [['Authorization' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TariffUpdateRequest')),
        tags: ['Admin Parking: Тарифы'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID тарифа', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/AccessDenied403', response: 403),
            new OA\Response(ref: '#/components/responses/NotFound404', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function update(int $id, UpdateRequest $request): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());
        $tariff = $this->tariffCrudService->update($id, RequestHelper::getParkingIds($request), $dto);

        return response()->json(new TariffResponse($tariff));
    }

    /**
     * @throws NotFoundAppException
     * @throws ValidationAppException
     * @throws InvalidConfigurationAppException
     * @throws Throwable
     */
    #[OA\Post(
        path: '/parking/admin/tariffs/{id}/set-default',
        summary: 'Сделать тариф тарифом по умолчанию',
        description: 'Снимает флаг с прежнего тарифа парковки и ставит этому в одной транзакции; тариф парковки, недоступной администратору, — 404; параллельная смена тарифа по умолчанию той же парковки — 422',
        security: [['Authorization' => []]],
        tags: ['Admin Parking: Тарифы'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'ID тарифа', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/NotFound404', response: 404),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
            new OA\Response(ref: '#/components/responses/ServerError500', response: 500),
        ]
    )]
    public function setDefault(int $id, Request $request): JsonResponse
    {
        $tariff = $this->setDefaultTariffCommand->execute($id, RequestHelper::getParkingIds($request));

        return response()->json(new TariffResponse($tariff));
    }
}
