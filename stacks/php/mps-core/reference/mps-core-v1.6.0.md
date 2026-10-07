# mps/core v1.6.0

## src

- class `MPS\Core\Application` _extends LaravelApplication_ — Для корректного определения окружений в соответствии с \MPS\Core\Enums\EnvEnum
  - `isLocal()`
  - `isProduction()`
  - `runningUnitTests()`
- class `MPS\Core\ServiceProvider` _extends CoreServiceProvider_
  - `register()`
  - `boot()`

## src/Commands

- class `MPS\Core\Commands\Command` _implements CommandInterface_
  - `exec(): mixed`
- interface `MPS\Core\Commands\CommandInterface`
  - `exec(): mixed`

## src/Components/CRUD/DataObjects

- class `MPS\Core\Components\CRUD\DataObjects\CommandDataObject` _extends DataObject implements CommandDataObjectInterface_
  - `getAction(): CommandEnum`
  - `setAction(CommandEnum $action): self`
  - `getCondition(): Closure|array`
  - `setCondition(Closure|array $condition): self`
  - `getData(): array`
  - `setData(DataObjectInterface|array $data): self`
  - `isSuccess(): bool`
  - `setSuccess(bool $success): self`
  - `getModel(): ?Model`
  - `setModel(?Model $model): self`
  - `getEntityId(string $key = 'id'): mixed` — Пытаемся получить идентификатор сущности с которой происходят действия
  - `processDataCallback(Closure $function): void`
- class `MPS\Core\Components\CRUD\DataObjects\CreateCommandDataObject` _extends CommandDataObject implements ModifyCommandDataObjectInterface_
  - `__construct()`
- class `MPS\Core\Components\CRUD\DataObjects\DeleteCommandDataObject` _extends CommandDataObject_
  - `__construct()`
- class `MPS\Core\Components\CRUD\DataObjects\PaginationSearchResult` _extends DataObject_
  - `getItems(): Collection`
  - `setItems(Collection $items): self`
  - `getPagination(): LengthAwarePaginator`
  - `setPagination(LengthAwarePaginator $pagination): self`
  - `getParams(): array`
  - `setParams(array $params): self`
- class `MPS\Core\Components\CRUD\DataObjects\SearchDataObject` _extends DataObject_
  - `getParams(): array`
  - `setParams(array $params): self`
  - `getId(): mixed`
  - `setId(mixed $id): self`
  - `getPerPage(): bool|int|null`
  - `setPerPage(bool|int|null $perPage): self`
  - `getPage(): ?int`
  - `setPage(?int $page): self`
  - `getItems(): Collection|array`
  - `setItems(Collection|array $values): self`
  - `processItemsCallback(Closure $function): void`
  - `processParamsCallback(Closure $function): void`
- class `MPS\Core\Components\CRUD\DataObjects\UpdateCommandDataObject` _extends CommandDataObject implements ModifyCommandDataObjectInterface_
  - `__construct()`

## src/Components/CRUD/Enums

- enum `MPS\Core\Components\CRUD\Enums\CommandEnum` _: string implements Enumerable_
  - кейсы: `CREATE`, `UPDATE`, `DELETE`
  - `static labels(): array`

## src/Components/CRUD/Events

- class `MPS\Core\Components\CRUD\Events\AfterDeleteEvent` _extends CoreEvent_
  - `__construct(protected CRUDServiceInterface $service, protected DeleteCommandDataObject $commandDataObject)`
  - `getService(): CRUDServiceInterface`
  - `getDto(): DeleteCommandDataObject`
- class `MPS\Core\Components\CRUD\Events\AfterModifyEvent` _extends CoreEvent_
  - `__construct(protected CRUDServiceInterface $service, protected ModifyCommandDataObjectInterface $commandDataObject)`
  - `getService(): CRUDServiceInterface`
  - `getDto(): ModifyCommandDataObjectInterface`
- class `MPS\Core\Components\CRUD\Events\AfterSearchEvent` _extends CoreEvent_
  - `__construct(protected SearchAwareInterface $service, protected SearchDataObject $searchDataObject)`
  - `getService(): SearchAwareInterface`
  - `getDto(): SearchDataObject`
- class `MPS\Core\Components\CRUD\Events\BeforeDeleteEvent` _extends CoreEvent_
  - `__construct(protected CRUDServiceInterface $service, protected DeleteCommandDataObject $commandDataObject)`
  - `getService(): CRUDServiceInterface`
  - `getDto(): DeleteCommandDataObject`
- class `MPS\Core\Components\CRUD\Events\BeforeModifyEvent` _extends CoreEvent_
  - `__construct(protected CRUDServiceInterface $service, protected ModifyCommandDataObjectInterface $commandDataObject)`
  - `getService(): CRUDServiceInterface`
  - `getDto(): ModifyCommandDataObjectInterface`

## src/Components/CRUD/Http/Actions

- class `MPS\Core\Components\CRUD\Http\Actions\CreateAction` _extends Action_
  - `run(): JsonResponse`
- class `MPS\Core\Components\CRUD\Http\Actions\DeleteAction` _extends Action_
  - `run(int $id): JsonResponse`
- class `MPS\Core\Components\CRUD\Http\Actions\IndexAction` _extends Action_
  - `run(): JsonResponse`
- class `MPS\Core\Components\CRUD\Http\Actions\UpdateAction` _extends Action_
  - `run(int $id): JsonResponse`
- class `MPS\Core\Components\CRUD\Http\Actions\ViewAction` _extends Action_
  - `run(int $id): JsonResponse`

## src/Components/CRUD/Http/Controllers

- class `MPS\Core\Components\CRUD\Http\Controllers\CRUDController` _extends Controller implements CRUDServiceAwareInterface_

## src/Components/CRUD/Interfaces

- interface `MPS\Core\Components\CRUD\Interfaces\CommandDataObjectInterface` _extends DataObjectInterface_
  - `getAction(): CommandEnum`
  - `setAction(CommandEnum $action): self`
  - `getCondition(): Closure|array`
  - `setCondition(Closure|array $condition): self`
  - `getData(): array`
  - `setData(DataObjectInterface|array $data): self`
  - `isSuccess(): bool`
  - `setSuccess(bool $success): self`
  - `getModel(): ?Model`
  - `setModel(?Model $model): self`
  - `getEntityId(string $key = 'id'): mixed` — Пытаемся получить идентификатор сущности с которой происходят действия
  - `processDataCallback(Closure $function): void`
- interface `MPS\Core\Components\CRUD\Interfaces\ModifyCommandDataObjectInterface` _extends CommandDataObjectInterface_
- interface `MPS\Core\Components\CRUD\Interfaces\SearchAwareInterface`
  - `search(SearchDataObject $dto, ?callable $callback = null, bool $asArray = true): array`
  - `searchById(int|string $id, ?string $notFoundMessage = null): array`
  - `searchByIds(array $ids, ?callable $callback = null, bool $keyBy = true): array`

## src/Components/CRUD/Repositories/Database/Eloquent

- class `MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepository` _extends Repository implements CRUDRepositoryInterface_
  - `create(array $data): Model`
  - `oneByCondition(array|Closure $condition): ?Model`
  - `allByCondition(array|Closure $condition): Collection`
  - `countByCondition(array|Closure $condition): int`
  - `isUnique(array|Closure $condition, int|string|null $excludeId = null): bool`
  - `oneById(int|array|string $id): ?Model`
  - `oneByIdOrFail(int|array|string $id, ?string $message = null): Model`
  - `search(array $params = [], int|array|string|null $id = null, int $perPage = 30, ?int $page = null, ?BuilderContract $query = null): LengthAwarePaginatorContract|Collection`
  - `update(array|Closure $condition, array $data): bool`
  - `updateById(int|array|string $id, array $data): bool`
  - `upsert(array $values, array|string $uniqueBy, array|null $update = null): bool`
  - `upsertViaModel(array $values, array|string $uniqueBy, array|null $update = null): bool`
  - `delete(array|Closure $condition): bool`
  - `deleteById(array|int|string $id): bool`
- interface `MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryAwareInterface`
  - `getRepository(): ?CRUDRepositoryInterface`
- trait `MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryAwareTrait`
  - `getRepository(): ?CRUDRepositoryInterface`
- interface `MPS\Core\Components\CRUD\Repositories\Database\Eloquent\CRUDRepositoryInterface` _extends RepositoryInterface_
  - `create(array $data): Model`
  - `oneByCondition(array|Closure $condition): ?Model`
  - `allByCondition(array|Closure $condition): Collection`
  - `countByCondition(array|Closure $condition): int`
  - `isUnique(array|Closure $condition, int|string|null $excludeId = null): bool`
  - `oneById(int|array|string $id): ?Model`
  - `oneByIdOrFail(int|array|string $id, ?string $message = null): Model`
  - `search(array $params = [], int|array|string|null $id = null, int $perPage = 30): LengthAwarePaginatorContract|Collection`
  - `update(array|Closure $condition, array $data): bool`
  - `updateById(int|array|string $id, array $data): bool`
  - `upsert(array $values, array|string $uniqueBy, array|null $update = null): bool`
  - `upsertViaModel(array $values, array|string $uniqueBy, array|null $update = null): bool`
  - `delete(array|Closure $condition): bool`
  - `deleteById(array|int|string $id): bool`

## src/Components/CRUD/Services

- class `MPS\Core\Components\CRUD\Services\CRUDService` _extends Service implements CRUDServiceInterface_
- interface `MPS\Core\Components\CRUD\Services\CRUDServiceAwareInterface`
  - `getService(): ?CRUDServiceInterface`
  - `setService(CRUDServiceInterface $service): self`
- trait `MPS\Core\Components\CRUD\Services\CRUDServiceAwareTrait`
  - `getService(): ?CRUDServiceInterface`
  - `setService(CRUDServiceInterface $service): self`
- interface `MPS\Core\Components\CRUD\Services\CRUDServiceInterface` _extends ServiceInterface, SearchAwareInterface_
  - `create(CreateCommandDataObject $dto): mixed`
  - `update(UpdateCommandDataObject $dto): bool`
  - `updateById(string|int $id, array $data): bool`
  - `delete(DeleteCommandDataObject $dto): bool`
  - `deleteById(int|string $id, bool $checkExistence = true): bool`

## src/Components/CRUD/Services/Database/Eloquent

- class `MPS\Core\Components\CRUD\Services\Database\Eloquent\CRUDService` _extends CoreCRUDService implements CRUDServiceInterface_
  - `__construct()`
  - `create(CreateCommandDataObject $dto): Model`
  - `searchById(int|string $id, ?string $notFoundMessage = null): array`
  - `searchByModel(Model $model): array`
  - `update(UpdateCommandDataObject $dto): bool`
  - `updateById(string|int $id, array $data): bool`
  - `updateByModel(Model $model, array $data): bool`
  - `delete(DeleteCommandDataObject $dto): bool`
  - `deleteById(int|string $id, bool $checkExistence = true): bool`
  - `deleteByModel(Model $model): bool`
  - `truncate(): void`
- interface `MPS\Core\Components\CRUD\Services\Database\Eloquent\CRUDServiceInterface` _extends CoreCRUDServiceInterface, CRUDRepositoryAwareInterface_
  - `create(CreateCommandDataObject $dto): Model`
  - `searchByModel(Model $model): array`
  - `update(UpdateCommandDataObject $dto): bool`
  - `updateByModel(Model $model, array $data): bool`
  - `delete(DeleteCommandDataObject $dto): bool`
  - `deleteByModel(Model $model): bool`
  - `truncate(): void`

## src/Components/CRUD/Traits

- trait `MPS\Core\Components\CRUD\Traits\SearchAwareTrait`
  - `search(SearchDataObject $dto, ?callable $callback = null, bool $asArray = true): array`
  - `searchById(int|string $id, ?string $notFoundMessage = null): array`
  - `searchByIds(array $ids, ?callable $callback = null, bool $keyBy = true): array`

## src/Components/Database

- class `MPS\Core\Components\Database\SchemaDataProvider`
  - `static table(string $table): array`
  - `static column(string $table, string $column): array`
  - `static path(): string`

## src/Components/Database/Commands

- class `MPS\Core\Components\Database\Commands\SchemaCacheCommand` _extends Command_
  - `__construct(protected ConnectionInterface $connection, protected Filesystem $fileSystem)`

## src/Components/Database/Console

- class `MPS\Core\Components\Database\Console\SchemaPreloadCommand` _extends Command_
  - `handle(SchemaCacheCommand $command): void`

## src/Components/ItemsContainer

- class `MPS\Core\Components\ItemsContainer\ItemsContainer` _extends DataObject implements ItemsContainerInterface_
- interface `MPS\Core\Components\ItemsContainer\ItemsContainerInterface` _extends DataObjectInterface, \IteratorAggregate_
  - `setItems(iterable $items, bool $fresh = true): static`
  - `getItems(): array`
  - `fresh(): void`
  - `getIterator(): ArrayIterator`
- trait `MPS\Core\Components\ItemsContainer\ItemsContainerTrait`
  - `setItems(iterable $items, bool $fresh = true): static`
  - `getItems(): array`
  - `fresh(): void`
  - `toArray(StringCaseEnum $case = StringCaseEnum::DEFAULT, bool $recursive = false): array`
  - `getIterator(): ArrayIterator`

## src/DataObjects

- class `MPS\Core\DataObjects\DataObject` _implements DataObjectInterface_
  - `fromArray(array $data): static`
- class `MPS\Core\DataObjects\DataObjectCollection` _extends DataObject implements DataObjectCollectionInterface_
- interface `MPS\Core\DataObjects\DataObjectCollectionInterface` _extends ItemsContainerInterface_
  - `pushItem(array|DataObjectInterface $item): static`
  - `getItems(): array`
- trait `MPS\Core\DataObjects\DataObjectCollectionTrait`
  - `setItems(iterable $items, bool $fresh = true): static`
  - `pushItem(array|DataObjectInterface $item): static`
  - `getItems(): array`
- interface `MPS\Core\DataObjects\DataObjectInterface` _extends Arrayable, Validatable, MakeAwareInterface_

## src/Entitites

- class `MPS\Core\Entitites\Entity` _implements EntityInterface, Validatable, Arrayable_
- interface `MPS\Core\Entitites\EntityInterface`

## src/Enums

- class `MPS\Core\Enums\AppErrorTypeEnum` _extends Enum_
  - константы: `NOT_FOUND`, `UNAUTHORIZED`, `ACCESS_DENIED`, `NOT_VALID`, `OPERATION_FAILED`, `BAD_REQUEST`, `VALIDATION_ERROR`, `INTERNAL_SERVER_ERROR`, `METHOD_NOT_ALLOWED`, `TOO_MANY_REQUESTS`
  - `static statusByType(string $type): int`
  - `static typeByStatus(int $status): string`
- enum `MPS\Core\Enums\CountryCodeEnum` _: string implements Enumerable_
  - кейсы: `KZ`
- enum `MPS\Core\Enums\CurrencyEnum` _: string implements Enumerable_
  - кейсы: `KZT`, `USD`
- class `MPS\Core\Enums\Enum` _implements Enumerable_
- interface `MPS\Core\Enums\Enumerable`
  - `static all(): array`
  - `static values(): array`
  - `static keys(): array`
  - `static labels(): array`
  - `static exclude(): array`
  - `static list(bool $reverse = false, bool $labelsAsKeys = false): array`
  - `static key(mixed $value, mixed $default = null): mixed`
  - `static label(mixed $key, mixed $default = null, string $functionName = 'labels'): mixed`
  - `static toString(): string`
  - `getLabel(mixed $default = null, string $functionName = 'labels'): mixed`
  - `hasLabel(string $functionName = 'labels'): bool`
- trait `MPS\Core\Enums\EnumerableTrait`
  - `static all(): array`
  - `static values(): array`
  - `static keys(): array`
  - `static labels(): array`
  - `static exclude(): array`
  - `static list(bool $reverse = false, bool $labelsAsKeys = false): array`
  - `static key(mixed $value, mixed $default = null): mixed`
  - `static label(mixed $key, mixed $default = null, string $functionName = 'labels'): mixed`
  - `static toString(): string`
  - `getLabel(mixed $default = null, string $functionName = 'labels'): mixed`
  - `hasLabel(string $functionName = 'labels'): bool`
- enum `MPS\Core\Enums\EnvEnum` _: string implements Enumerable_
  - кейсы: `LOCAL`, `STAGE`, `TESTING`, `PROD`, `PRE_PROD`
  - `static fromAppConfig(string $key = 'app.env'): ?self`
  - `is(self $value): bool`
- class `MPS\Core\Enums\LanguageNumberEnum` _extends Enum_
  - константы: `RU`, `KK`, `EN`
  - `static default(): int`
- class `MPS\Core\Enums\LanguageStringEnum` _extends Enum_
  - константы: `RU`, `KK`, `EN`
  - `static default(): string`
- enum `MPS\Core\Enums\LocaleEnum` _: string implements Enumerable_
  - кейсы: `RU`, `KK`, `EN`
- class `MPS\Core\Enums\StatusEnum` _extends Enum_
  - константы: `ACTIVE`, `DISABLED`
  - `static labels(): array`
- enum `MPS\Core\Enums\StringCaseEnum` _: int implements Enumerable_
  - кейсы: `DEFAULT`, `SNAKE`, `CAMEL`, `KEBAB`

## src/Events

- class `MPS\Core\Events\Event`

## src/Exceptions

- class `MPS\Core\Exceptions\AccessDeniedAppException` _extends AppException_
  - `__construct(?string $message = null)`
- interface `MPS\Core\Exceptions\AppErrorCodeEnumInterface` _extends Enumerable, BackedEnum_
- class `MPS\Core\Exceptions\AppException` _extends Exception implements AppExceptionInterface_
  - `__construct(string|array $message, string $errorType, ?int $statusCode = null, array $details = [], ?Throwable $previous = null)`
  - `setMessage(string $value): void`
  - `setLine(int $line): static`
  - `setFile(string $filePath): static`
  - `getErrorType(): string`
  - `getStatusCode(): int`
  - `getDetails(): array`
  - `isTrackable(): bool`
  - `setIsTrackable(bool $value): static`
  - `getAppErrorCode(): AppErrorCodeEnumInterface|int`
  - `setAppErrorCode(AppErrorCodeEnumInterface|int $value): static`
  - `render(Request $request, ?Throwable $exception = null): JsonResponse`
- interface `MPS\Core\Exceptions\AppExceptionInterface`
  - `setLine(int $line): static`
  - `setFile(string $filePath): static`
  - `getErrorType(): string`
  - `getStatusCode(): int`
  - `getDetails(): array`
  - `isTrackable(): bool`
  - `setIsTrackable(bool $value): static`
  - `getAppErrorCode(): AppErrorCodeEnumInterface|int`
  - `setAppErrorCode(AppErrorCodeEnumInterface|int $value): static`
  - `render(Request $request, ?Throwable $exception = null): JsonResponse`
- class `MPS\Core\Exceptions\BadRequestAppException` _extends AppException_
  - `__construct(?string $message = null, array $details = [])`
- class `MPS\Core\Exceptions\Handler` _extends ExceptionHandler_
  - `report(Throwable $e): void`
  - `render($request, Throwable $e): JsonResponse|RedirectResponse|Response|SymfonyResponse`
- class `MPS\Core\Exceptions\InvalidConfigurationAppException` _extends AppException_
  - `__construct(?string $message = null, array $details = [])`
- class `MPS\Core\Exceptions\NotFoundAppException` _extends AppException_
  - `__construct(?string $message = null)`
- class `MPS\Core\Exceptions\OperationFailedAppException` _extends AppException_
  - `__construct(?string $message = null, array $details = [])`
- class `MPS\Core\Exceptions\TooManyRequestsAppException` _extends AppException_
  - `__construct(?string $message = null, array $details = [])`
- class `MPS\Core\Exceptions\UnAuthorizeAppException` _extends AppException_
  - `__construct(?string $message = null)`
- class `MPS\Core\Exceptions\UnTrackableAppException` _extends AppException_
  - `__construct(string|array $message, string $errorType, ?int $statusCode = null, array $details = [], ?Throwable $previous = null)`
  - `setIsTrackable(bool $value): static`
- class `MPS\Core\Exceptions\ValidationAppException` _extends AppException_
  - `__construct(?string $message = null, array $details = [], bool $isTrackable = true)`

## src/Helpers

- class `MPS\Core\Helpers\AppHelper`
  - `static setDefinitions(Application $app, array $definitions = []): bool`
- class `MPS\Core\Helpers\AssertHelper` _extends BaseAssert_
- class `MPS\Core\Helpers\DBSortHelper`
  - `static getClearAttribute(string $value): string`
  - `static getSortingOrder(string $value): string`
- class `MPS\Core\Helpers\EnvHelper`
  - `static is(EnvEnum $enum): bool`
- class `MPS\Core\Helpers\InstanceHelper`
  - `static reflection(object|string $objectOrClass): ReflectionClass`
  - `static isExists(string $className, bool $throw = false): ?ReflectionClass`
  - `static instanceOf(string $className, string $instanceOfClassName, bool $throw = false): bool`
  - `static shortName(object|string $objectOrClass, string $cutPart = '', bool $capitalize = false): string`
  - `static createObject(array $data, ?string $className = null): object`
  - `static dataObjectFromArray(DataObject $dataObject, array $data): DataObject`
  - `static objectFromArray(object $object, array $data = [], ?callable $callback = null): object` — Заполняет объект данными из массива Если объект имеет объектные свойства (object-typed property) функция будет работать рекурсивно
- class `MPS\Core\Helpers\JsonHelper`
  - `static encode(array $value, int $options = self::DEFAULT_OPTIONS): string`
  - `static decode(string $json, bool $associative = true): mixed`
  - `static htmlEncode(array $value): string`
  - `static isJson(string $string, ?array &$decode = null): bool`
  - `static getSizeInBytes(array $value, int $options = self::DEFAULT_OPTIONS): int`
- class `MPS\Core\Helpers\ReflectionHelper` — Хелпер для работы с рефлексией
  - `static reflection(object|string $objectOrClass): ReflectionClass` — Получить ReflectionClass с кэшем
  - `static getProperty(ReflectionClass $reflectionClass, string $name): \ReflectionProperty|null`
- class `MPS\Core\Helpers\RequestHelper`
  - `static getPerPageQueryParam(): array|string|null`
  - `static getPageParam(): array|string|null`
  - `static getSortParam(): array|string|null`
  - `static getQueryParam(string $param): array|string|null`
- class `MPS\Core\Helpers\ResponseHelper`
  - `static parse(array|null $payload): array`
  - `static isSuccess(array $payload): bool`
  - `static parseData(array|null $payload): array`
  - `static wrap(array $payload, bool $success): array`
  - `static empty(bool $success = false): Response` — Возвращает пустой ответ в формате приложения
- class `MPS\Core\Helpers\TransformHelper` — todo: реализация перегонки обеъектов, массивов

## src/Http/Actions

- class `MPS\Core\Http\Actions\Action` _implements ActionInterface_
  - `__construct()`
  - `getFormRequestClass(): string`
  - `setFormRequestClass(string $class): self`
  - `getResponseClass(): ?string`
  - `setResponseClass(string $class): self`
  - `getRequest(): ?FormRequestInterface`
  - `getResponse(mixed $resource): ?ResponseInterface`
- interface `MPS\Core\Http\Actions\ActionInterface`
  - `getFormRequestClass(): string`
  - `setFormRequestClass(string $class): self`
  - `getResponseClass(): ?string`
  - `setResponseClass(string $class): self`
  - `getRequest(): ?FormRequestInterface`
  - `getResponse(mixed $resource): ?ResponseInterface`

## src/Http/Controllers

- class `MPS\Core\Http\Controllers\Controller` _extends LaravelController implements ControllerInterface_
  - `__call($method, $parameters): mixed`
- interface `MPS\Core\Http\Controllers\ControllerInterface`
  - `__call($method, $parameters): mixed`

## src/Http/Requests

- class `MPS\Core\Http\Requests\ByIdsSearchRequest` _extends FormRequest_ — Поиск по идентификаторам
  - `authorize(): bool`
  - `rules()`
- class `MPS\Core\Http\Requests\CommandRequest` _extends FormRequest_
  - `validationData(): array|null|string`
- class `MPS\Core\Http\Requests\FormRequest` _extends LaravelFormRequest implements FormRequestInterface_
  - `label(string $attribute): mixed`
  - `defaults(): array`
  - `paramsCount(): int`
- interface `MPS\Core\Http\Requests\FormRequestInterface`
  - `label(string $attribute): mixed`
  - `defaults(): array`
  - `paramsCount(): int`
- class `MPS\Core\Http\Requests\SearchRequest` _extends FormRequest implements SearchRequestInterface_
  - `__construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)`
  - `validationData(): array|null|string`
  - `rules(): array`
  - `attributes(): array`
- interface `MPS\Core\Http\Requests\SearchRequestInterface`
- class `MPS\Core\Http\Requests\StatusChangeRequest` _extends CommandRequest_
  - `rules()`
  - `attributes()`

## src/Http/Responses

- class `MPS\Core\Http\Responses\JsonResponse` _extends Response_
- class `MPS\Core\Http\Responses\Response` _extends JsonResource implements ResponseInterface, MakeAwareInterface_
- interface `MPS\Core\Http\Responses\ResponseInterface`

## src/Interfaces

- interface `MPS\Core\Interfaces\Arrayable` _extends \Illuminate\Contracts\Support\Arrayable_
  - `toArray(StringCaseEnum $case = StringCaseEnum::DEFAULT, bool $recursive = false): array`
- interface `MPS\Core\Interfaces\Expandable`
  - `expand(string $value): self`
  - `expandCallable(?callable $callable = null): self`
  - `setExpanded(array $params): self`
  - `getExpanded(): array`
  - `hasExpand(string $value): bool`
  - `clearExpanded(): self`
- interface `MPS\Core\Interfaces\MakeAwareInterface`
  - `static make(...$parameters)`
- interface `MPS\Core\Interfaces\Validatable`
  - `validated(StringCaseEnum $case = StringCaseEnum::SNAKE): bool`
  - `getValidationErrors(): MessageBag`

## src/Models

- class `MPS\Core\Models\Model` _extends LaravelModel_

## src/Modules

- class `MPS\Core\Modules\ModuleServiceProvider` _extends ServiceProvider implements ModuleServiceProviderInterface_
  - `register(): void`
  - `boot(): void`
  - `static getLoadModules(): array`
  - `static getName(): string`
- interface `MPS\Core\Modules\ModuleServiceProviderInterface`

## src/Providers

- class `MPS\Core\Providers\ServiceProvider` _extends LaravelServiceProvider_

## src/Queries

- class `MPS\Core\Queries\Query` _implements QueryInterface_
  - `get(): mixed`
  - `setQueryParams(array $queryParams): self`
  - `pushQueryParam(string $key, mixed $value): self`
  - `setKeyBy(?string $keyBy): self`
- interface `MPS\Core\Queries\QueryInterface` _extends Expandable_
  - `get(): mixed`
  - `setQueryParams(array $queryParams): self`
  - `pushQueryParam(string $key, mixed $value): self`
  - `setKeyBy(?string $keyBy): self`

## src/Repositories

- class `MPS\Core\Repositories\Repository` _implements RepositoryInterface_
- interface `MPS\Core\Repositories\RepositoryAwareInterface`
  - `getRepository(): ?RepositoryInterface`
- trait `MPS\Core\Repositories\RepositoryAwareTrait`
  - `getRepository(): ?RepositoryInterface`
- interface `MPS\Core\Repositories\RepositoryInterface`

## src/Repositories/Database/Eloquent

- class `MPS\Core\Repositories\Database\Eloquent\Repository` _extends CoreRepository implements RepositoryInterface_
  - `__construct()`
  - `getModel(): Model`
  - `getTableName(): string`
  - `getConnection(): Connection`
  - `getColumns(): Collection`
  - `getPrimaryKeyName(): string|array`
  - `getQuery(bool $eloquent = true): EloquentBuilderContract|BuilderContract`
  - `transaction(Closure $callback): mixed`
  - `withoutEloquent(Closure $callback): mixed`
- interface `MPS\Core\Repositories\Database\Eloquent\RepositoryInterface`
  - `getModel(): Model`
  - `getTableName(): string`
  - `getConnection(): Connection`
  - `getColumns(): Collection`
  - `getPrimaryKeyName(): string|array`
  - `getQuery(bool $eloquent = true): EloquentBuilderContract|BuilderContract`
  - `transaction(Closure $callback): mixed`
  - `withoutEloquent(Closure $callback): mixed`

## src/Services

- class `MPS\Core\Services\Service` _implements ServiceInterface_
- interface `MPS\Core\Services\ServiceAwareInterface`
  - `getService(): ?ServiceInterface`
- trait `MPS\Core\Services\ServiceAwareTrait`
  - `getService(): ?ServiceInterface`
  - `setService(ServiceInterface $service): self`
- interface `MPS\Core\Services\ServiceInterface` _extends Expandable_

## src/Traits

- trait `MPS\Core\Traits\ArrayableTrait`
  - `toArray(StringCaseEnum $case = StringCaseEnum::DEFAULT, bool $recursive = false): array`
  - `toArrayWithSnakeKeys(bool $recursive = false): array`
  - `toArrayWithCamelKeys(bool $recursive = false): array`
- trait `MPS\Core\Traits\ExpandableTrait`
  - `expand(string $value): self`
  - `expandCallable(?callable $callable = null): self`
  - `setExpanded(array $params): self`
  - `getExpanded(): array`
  - `hasExpand(string $value): bool`
  - `clearExpanded(): self`
- trait `MPS\Core\Traits\MakeAwareTrait`
  - `static make(...$parameters)`
- trait `MPS\Core\Traits\ValidatableTrait`
  - `validated(StringCaseEnum $case = StringCaseEnum::SNAKE): bool`
  - `getValidationErrors(): MessageBag`

