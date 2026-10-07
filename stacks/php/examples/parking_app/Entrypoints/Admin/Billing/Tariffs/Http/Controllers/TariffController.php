<?php

declare(strict_types=1);

namespace App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\ParkingApp\Core\Resources\PaginateResource;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Commands\SetDefaultTariffCommand;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\DTO\TariffDto;
use App\ParkingApp\Domains\Billing\Modules\Tariffs\Services\TariffCrudServiceInterface;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\CreateRequest;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\IndexRequest;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Requests\Tariffs\UpdateRequest;
use App\ParkingApp\Entrypoints\Admin\Billing\Tariffs\Http\Responses\TariffResponse;
use App\ParkingApp\Workflows\Billing\Tariffs\CreateTariffWorkflow;
use Illuminate\Http\JsonResponse;
use MPS\Core\Components\CRUD\DataObjects\SearchDataObject;
use MPS\Core\Exceptions\NotFoundAppException;
use MPS\Core\Exceptions\ValidationAppException;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: 'Admin Parking: Тарифы', description: 'Управление тарифами парковок')]
class TariffController extends Controller
{
    public function __construct(
        private readonly TariffCrudServiceInterface $tariffCrudService,
        private readonly CreateTariffWorkflow $createTariffWorkflow,
        private readonly SetDefaultTariffCommand $setDefaultTariffCommand,
    ) {
    }

    #[OA\Get(
        path: '/parking/admin/tariffs',
        summary: 'Список тарифов',
        security: [['Authorization' => []]],
        tags: ['Admin Parking: Тарифы'],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestParkingId'),
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestStatus'),
            new OA\Parameter(ref: '#/components/parameters/TariffsIndexRequestSort'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffPaginatedData'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
        ]
    )]
    public function index(IndexRequest $request): JsonResponse
    {
        $search = new SearchDataObject();
        $search->setParams($request->validated());

        return response()->json(
            PaginateResource::make($this->tariffCrudService->search($search))
        );
    }

    /**
     * @throws NotFoundAppException
     */
    #[OA\Get(
        path: '/parking/admin/tariffs/{id}',
        summary: 'Тариф по ID',
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
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(response: 404, description: 'Тариф не найден'),
        ]
    )]
    public function view(int $id): JsonResponse
    {
        $item = $this->tariffCrudService->findById($id);

        if (! $item) {
            throw new NotFoundAppException('Тариф не найден');
        }

        return response()->json(new TariffResponse($item));
    }

    /**
     * @throws ValidationAppException
     */
    #[OA\Post(
        path: '/parking/admin/tariffs',
        summary: 'Создать тариф',
        security: [['Authorization' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TariffCreateRequest')),
        tags: ['Admin Parking: Тарифы'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Тариф создан',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
        ]
    )]
    public function create(CreateRequest $request): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());

        return response()->json(
            new TariffResponse($this->createTariffWorkflow->execute($dto)),
            201
        );
    }

    /**
     * @throws NotFoundAppException
     * @throws ValidationAppException
     */
    #[OA\Put(
        path: '/parking/admin/tariffs/{id}',
        summary: 'Изменить тариф',
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
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(response: 404, description: 'Тариф не найден'),
            new OA\Response(ref: '#/components/responses/ValidationError422', response: 422),
        ]
    )]
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        $dto = TariffDto::make()->fromArray($request->validated());

        return response()->json(
            new TariffResponse($this->tariffCrudService->update($id, $dto))
        );
    }

    /**
     * @throws NotFoundAppException
     * @throws Throwable
     */
    #[OA\Post(
        path: '/parking/admin/tariffs/{id}/set-default',
        summary: 'Сделать тариф тарифом по умолчанию',
        description: 'Снимает флаг с прежнего тарифа парковки и ставит этому в одной транзакции',
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
                    new OA\Property(property: 'data', ref: '#/components/schemas/TariffResponse'),
                ])
            ),
            new OA\Response(response: 404, description: 'Тариф не найден'),
        ]
    )]
    public function setDefault(int $id): JsonResponse
    {
        return response()->json(
            new TariffResponse($this->setDefaultTariffCommand->execute($id))
        );
    }
}
