# mps/utils v2.8.4

## src

- class `MPS\Utils\ServiceProvider` _extends CoreServiceProvider_
  - `boot(): void`
  - `register(): void`
- interface `MPS\Utils\SluggableInterface`
  - `sluggableAttribute(): string`

## src/Components/Authenticate/Basic

- class `MPS\Utils\Components\Authenticate\Basic\BasicAuthMiddleware`
  - `handle($request, Closure $next, string $group, string $configPath = 'basic-auth'): mixed`

## src/Components/Cache

- class `MPS\Utils\Components\Cache\CacheManager` _implements CacheManagerInterface_
  - `__construct(protected CacheRepository $repository)`
  - `setTtl(int $value): static`
  - `getCacheKeyByParts(string ...$parts): string`
  - `getOrCreate(string $key, \Closure $dataCallback, ?int $ttl = null): mixed`
  - `get(string $key): mixed`
  - `getMultiple(array $keys): array`
  - `getOrSet(string $key, \Closure $action, ?int $ttl = null): mixed`
  - `set(string $key, mixed $value, ?int $ttl = null): bool`
  - `setMultiple(array $values, ?int $ttl = null): bool`
  - `forget(string|array $key): bool`
  - `viaNull(\Closure $callback): mixed`
- interface `MPS\Utils\Components\Cache\CacheManagerAwareInterface`
  - `setCacheManager(CacheManagerInterface $cacheManager): void`
  - `getCacheManager(): ?CacheManagerInterface`
- interface `MPS\Utils\Components\Cache\CacheManagerInterface`
  - `setTtl(int $value): static`
  - `getCacheKeyByParts(string ...$args): string`
  - `get(string $key): mixed`
  - `getOrCreate(string $key, \Closure $dataCallback, ?int $ttl = null): mixed`
  - `set(string $key, mixed $value, ?int $ttl = null): bool`
  - `forget(string $key): bool`
- trait `MPS\Utils\Components\Cache\CacheMangerAwareTait` — Basic Implementation of CacheMangerAwareInterface.
  - `setCacheManager(CacheManagerInterface $cacheManager): void`
  - `getCacheManager(): ?CacheManagerInterface`
- class `MPS\Utils\Components\Cache\CallbackManager` _implements CallbackManagerInterface_
  - `__construct(private Container $container, private CacheManagerInterface $cache)`
  - `set(string $key, CallbackOptions $options, ?int $ttl = null): void`
  - `get(string $key): ?CallbackOptions`
  - `delete(string $key): void`
  - `dispatch(string $key): void`
  - `dispatchHandler(CallbackOptions $options): mixed`
- class `MPS\Utils\Components\Cache\SmartCache` _implements SmartCacheInterface_
  - `__construct(private CacheManagerInterface $cache, private TaggedCacheManagerInterface $tagged, private CallbackManagerInterface $callback)`
  - `get(string $key): mixed`
  - `getMultiple(array $keys): array`
  - `getByTag(string $tag): array`
  - `getOrSet(string $key, CallbackOptions $callbackOptions, CacheOptions $cacheOptions): mixed`
  - `set(string $key, mixed $value, CacheOptions $options): bool`
  - `setMultiple(array $values, CacheOptions $options): bool`
  - `forget(string|array $key): bool`
  - `invalidate(string $tag): bool`
  - `invalidateMultiple(array $tags): bool`
- class `MPS\Utils\Components\Cache\TaggedCacheManager` _implements TaggedCacheManagerInterface_
  - `__construct(private CacheManagerInterface $cache, private TagFactory $tagFactory)`
  - `getKeysByTag(string $tag): array` — Возвращает все ключи тега без загрузки значений из кеша.
  - `getByTag(string $tag): array` — Возвращает [key => value, ...], пропуская отсутствующие ключи.
  - `set(string $key, string $tag): bool`
  - `setMultiple(array $keys, string $tag): bool`
  - `invalidate(string $tag): bool`
  - `invalidateMultiple(array $tags): bool`

## src/Components/Cache/Console

- class `MPS\Utils\Components\Cache\Console\CacheTestCommand` _extends LaravelCommand_
  - `handle(CacheManagerInterface $cache, TaggedCacheManagerInterface $tagged, SmartCacheInterface $smart): void`
- class `MPS\Utils\Components\Cache\Console\CallbackClass` — Вспомогательный метод для теста CallbackOptions.
  - `resolveTestValue(string $key): string`

## src/Components/Cache/DataObjects

- class `MPS\Utils\Components\Cache\DataObjects\CacheOptions` _implements MakeAwareInterface_
  - `ttl(int $ttl): self`
  - `tags(string ...$tags): self`
  - `remember(): self`
  - `withHandler(CallbackOptions $handler): self`
- class `MPS\Utils\Components\Cache\DataObjects\CallbackOptions` _implements MakeAwareInterface_
  - `__construct(private string $class, private string $method, private array $params = [])`
  - `static fromArray(array $data): self`
  - `toArray(): array`
  - `getClass(): string`
  - `getMethod(): string`
  - `getParams(): array`

## src/Components/Cache/Interfaces

- interface `MPS\Utils\Components\Cache\Interfaces\CacheManagerInterface` _extends DeprecatedCacheManagerInterface_
  - `setTtl(int $value): static`
  - `getCacheKeyByParts(string ...$parts): string`
  - `getOrCreate(string $key, \Closure $dataCallback, ?int $ttl = null): mixed`
  - `get(string $key): mixed`
  - `getMultiple(array $keys): array`
  - `getOrSet(string $key, \Closure $action, ?int $ttl = null): mixed`
  - `set(string $key, mixed $value, ?int $ttl = null): bool`
  - `setMultiple(array $values, ?int $ttl = null): bool`
  - `forget(string|array $key): bool`
  - `viaNull(\Closure $callback): mixed`
- interface `MPS\Utils\Components\Cache\Interfaces\CallbackManagerInterface`
  - `set(string $key, CallbackOptions $options, ?int $ttl = null): void`
  - `get(string $key): ?CallbackOptions`
  - `delete(string $key): void`
  - `dispatch(string $key): void`
  - `dispatchHandler(CallbackOptions $options): mixed`
- interface `MPS\Utils\Components\Cache\Interfaces\SmartCacheInterface`
  - `get(string $key): mixed`
  - `getMultiple(array $keys): array`
  - `getByTag(string $tag): array`
  - `getOrSet(string $key, CallbackOptions $callbackOptions, CacheOptions $cacheOptions): mixed`
  - `set(string $key, mixed $value, CacheOptions $options): bool`
  - `setMultiple(array $values, CacheOptions $options): bool`
  - `forget(string|array $key): bool`
  - `invalidate(string $tag): bool`
  - `invalidateMultiple(array $tags): bool`
- interface `MPS\Utils\Components\Cache\Interfaces\TaggedCacheManagerInterface`
  - `getKeysByTag(string $tag): array` — Возвращает все ключи тега без загрузки значений из кеша.
  - `getByTag(string $tag): array` — Возвращает [key => value, ...], пропуская отсутствующие ключи.
  - `set(string $key, string $tag): bool`
  - `setMultiple(array $keys, string $tag): bool`
  - `invalidate(string $tag): bool`
  - `invalidateMultiple(array $tags): bool`

## src/Components/Cache/Providers

- class `MPS\Utils\Components\Cache\Providers\ServiceProvider` _extends ModuleServiceProvider_
  - `static getName(): string`

## src/Components/Database

- class `MPS\Utils\Components\Database\SlowQueryDetector`
  - `__construct(protected LoggerInterface $logger)`
  - `__invoke(): bool`

## src/Components/Documentation/Swagger

- class `MPS\Utils\Components\Documentation\Swagger\SwaggerHelper`
  - `static parseDocComment(?string $source, ?callable $callback = null): ?array`

## src/Components/Documentation/Swagger/SwaggerScan

- class `MPS\Utils\Components\Documentation\Swagger\SwaggerScan\ScannerCommand` _extends Command_
  - `handle()`

## src/Components/Documentation/Swagger/SwaggerScan/DataObjects

- class `MPS\Utils\Components\Documentation\Swagger\SwaggerScan\DataObjects\Annotation` _extends DataObject_
  - `__construct(private string $source, private ?string $method = null, private ?string $path = null, private ?string $request = null)`
  - `getSource(): string`
  - `setSource(string $value): self`
  - `getMethod(): ?string`
  - `setMethod(?string $value): self`
  - `getRequest(): ?string`
  - `setRequest(?string $value): self`
  - `getPath(): ?string`
  - `setPath(?string $value): self`
- class `MPS\Utils\Components\Documentation\Swagger\SwaggerScan\DataObjects\Error` _extends DataObject_
  - `__construct(private ErrorTypeEnum $type, private Route $route, private ?Annotation $annotation = null)`
  - `getType(): ErrorTypeEnum`
  - `setType(ErrorTypeEnum $value): self`
  - `getAnnotation(): ?Annotation`
  - `setAnnotation(?Annotation $value): self`
  - `getRoute(): Route`
  - `setRoute(Route $value): self`
- class `MPS\Utils\Components\Documentation\Swagger\SwaggerScan\DataObjects\Route` _extends DataObject_
  - `__construct(private ?string $controller = null, private ?string $action = null, private ?string $uri = null, private ?string $method = null)`
  - `getController(): ?string`
  - `setController(?string $value): self`
  - `getAction(): ?string`
  - `setAction(?string $value): self`
  - `getUri(): ?string`
  - `setUri(?string $value): self`
  - `getMethod(): ?string`
  - `setMethod(?string $value): self`
  - `isFullInfo(): bool`

## src/Components/Documentation/Swagger/SwaggerScan/Enums

- enum `MPS\Utils\Components\Documentation\Swagger\SwaggerScan\Enums\ErrorTypeEnum` _: string implements Enumerable_
  - кейсы: `UNPROCESSED`, `NOT_FOUND_FUNCTION`, `NOT_FOUND_ANNOTATION`, `NOT_VALID_METHOD`, `NOT_VALID_PATH`, `NOT_VALID_REQUEST`, `NOT_VALID_RESPONSE`
- enum `MPS\Utils\Components\Documentation\Swagger\SwaggerScan\Enums\ResultTypeEnum` _: int implements Enumerable_
  - кейсы: `TOTAL`, `IGNORED`, `VALID`, `IN_VALID`
  - `static labels(): array`

## src/Components/ElasticSearch

- class `MPS\Utils\Components\ElasticSearch\Client` _extends EnsiElasticClient implements ClientInterface_
  - `create(string $index, string|int $id, array $data): array`
  - `update(string $index, string|int $id, array $data): array`
  - `index(string $index, string|int $id, array $data): array`
  - `delete(string $index, string|int $id): array`
  - `search(string $indexName, array $dsl, ?string $searchType = null, array $options = []): array|Promise`
  - `getMappingByIndex(string $index, array $params = []): array`
  - `cloneIndex(string $index, string $targetIndex): array`
  - `updateIndexSettings(string $index, array $data): array`
  - `getIndexSettings(string $index): array`
  - `createIndexAlias(string $index, string $alias, array $body = []): array`
  - `deleteIndexAliases(string $index, array $aliases): array`
  - `getIndexAliases(string $index): array`
  - `indexAliasExist(string $index, string $alias): bool`
  - `bulkUpdateAliases(array $actions): array`
- class `MPS\Utils\Components\ElasticSearch\SearchIndex` _extends ElasticIndex_
  - `static query(): SearchQuery`
  - `getName(): string`
  - `documentCreate(int|string $id, array $data): array|Promise`
  - `documentUpdate(int|string $id, array $data): array|Promise`
  - `documentIndex(int|string $id, array $data): array|Promise`
  - `mappings(): IndexMapping`
  - `clone(string $newIndexName): array|Promise`
  - `updateSettings(array $data): array|Promise`
  - `getMapping(array $params = []): array`
  - `getSettings(): array|Promise`
  - `search(array $dsl, ?string $searchType = null, array $options = []): array|Promise`
- class `MPS\Utils\Components\ElasticSearch\ServiceProvider` _extends ModuleServiceProvider_
  - `register(): void`

## src/Components/ElasticSearch/Actions

- class `MPS\Utils\Components\ElasticSearch\Actions\BaseAction` _extends DataObject implements ActionInterface_
  - `__construct(string|int $id)`
  - `getAction(): string`
  - `setAction(string $action): self`
  - `getId(): int|string`
  - `setId(int|string $id): self`
  - `getIndex(): string`
  - `setIndex(string $index): self`
  - `toBulk(): array`
- class `MPS\Utils\Components\ElasticSearch\Actions\CreateAction` _extends BaseAction_
- class `MPS\Utils\Components\ElasticSearch\Actions\DeleteAction` _extends BaseAction_
- class `MPS\Utils\Components\ElasticSearch\Actions\IndexAction` _extends BaseAction_
- class `MPS\Utils\Components\ElasticSearch\Actions\UpdateAction` _extends BaseAction_

## src/Components/ElasticSearch/DataObjects

- class `MPS\Utils\Components\ElasticSearch\DataObjects\Document` _extends DataObject_
  - `getIndex(): string`
  - `setIndex(string $index): self`
  - `getId(): string`
  - `setId(string $id): self`
  - `getScore(): ?string`
  - `setScore(?string $score): self`
  - `getSource(): array`
  - `setSource(array $source): self`
  - `getSort(): array`
  - `setSort(array $sort): self`
- class `MPS\Utils\Components\ElasticSearch\DataObjects\IndexMapping` _extends DataObject_
  - `getDynamic(): DynamicMappingEnum`
  - `setDynamic(string|DynamicMappingEnum $dynamic): self`
  - `getProperties(): array`
  - `setProperties(array $properties): self`

## src/Components/ElasticSearch/Enums

- enum `MPS\Utils\Components\ElasticSearch\Enums\DynamicMappingEnum` _: string implements Enumerable_
  - кейсы: `TRUE`, `FALSE`, `STRICT`, `RUNTIME`

## src/Components/ElasticSearch/Interfaces

- interface `MPS\Utils\Components\ElasticSearch\Interfaces\ActionInterface`
  - `getAction(): string`
  - `getIndex(): string`
  - `setIndex(string $index): self`
  - `getId(): string|int`
  - `setId(int|string $id): self`
  - `toBulk(): array`
- interface `MPS\Utils\Components\ElasticSearch\Interfaces\CRUDRepositoryInterface` _extends RepositoryInterface_
  - `create(CreateAction $action): array`
  - `update(UpdateAction $action): array`
  - `index(IndexAction $action): array`
  - `delete(DeleteAction $action): array`
  - `bulk(array $actions): array`
  - `search(array $params = [], int|array|string|null $id = null, int $perPage = 30, ?int $page = null, ?SearchQuery $query = null): LengthAwarePaginatorInterface|Collection|Document|null`
  - `oneById(int|string $id): ?Document`
  - `oneByIdOrFail(int|string $id, ?string $message = null): Document`
  - `oneByCondition(Closure $condition): ?Document`
  - `allByCondition(Closure $condition): Collection`
- interface `MPS\Utils\Components\ElasticSearch\Interfaces\ClientInterface`
  - `getClient(): Client`
  - `create(string $index, string|int $id, array $data): array`
  - `update(string $index, string|int $id, array $data): array`
  - `index(string $index, string|int $id, array $data): array`
  - `delete(string $index, string|int $id): array`
  - `getMappingByIndex(string $index): array`
  - `search(string $indexName, array $dsl, ?string $searchType = null, array $options = []): array|Promise`
  - `termvectors(string $indexName, array $dsl): array|Promise`
  - `get(string $indexName, int|string $id): array|Promise`
  - `indicesExists(string $index): bool|Promise`
  - `indicesCreate(string $index, array $settings): ?Promise`
  - `bulk(?string $index, array $body): array|Promise`
  - `deleteByQuery(string $indexName, array $dsl): array|Promise`
  - `documentDelete(string $index, int|string $id): array|Promise`
  - `catIndices(string $indexName, ?array $getFields = null): array`
  - `indicesInfo(?array $indices = [], array $columns = ['i'], array $sort = [], ?string $health = null): array|Promise`
  - `indicesDelete(string $indexName): array|Promise`
  - `indicesRefresh(string $indexName): array|Promise`
  - `indicesReloadSearchAnalyzers(string $indexName): array|Promise`
  - `enableQueryLog(): void`
  - `disableQueryLog(): void`
  - `getQueryLog(): Collection`
  - `static fromConfig(array $config): static`
  - `static resolveBasicAuthData(array $config): array`
  - `cloneIndex(string $index, string $targetIndex): array`
  - `updateIndexSettings(string $index, array $data): array`
  - `getIndexSettings(string $index): array`
  - `createIndexAlias(string $index, string $alias, array $body = []): array`
  - `deleteIndexAliases(string $index, array $aliases): array`
  - `getIndexAliases(string $index): array`
  - `indexAliasExist(string $index, string $alias): bool`
  - `bulkUpdateAliases(array $actions): array`
- interface `MPS\Utils\Components\ElasticSearch\Interfaces\QueryFilterInterface`
  - `apply(BoolQuery $query): void`
- interface `MPS\Utils\Components\ElasticSearch\Interfaces\RepositoryInterface`
  - `getIndex(): SearchIndex`
  - `getIndexName(): string`
  - `initIndex(): bool`
  - `dropIndex(): bool`
  - `getColumns(): Collection`
  - `getPrimaryKeyName(): string`
  - `getQuery(): SearchQuery`

## src/Components/ElasticSearch/Paginators

- class `MPS\Utils\Components\ElasticSearch\Paginators\LengthAwarePaginator` _extends LaravelPaginator implements LengthAwarePaginatorInterface_
  - `__construct($items, $total, $perPage, $currentPage = null, array $options = [], array $aggregation = [])` — Create a new paginator instance.
  - `getAggregation(): array`
  - `toArray(): array`
- interface `MPS\Utils\Components\ElasticSearch\Paginators\LengthAwarePaginatorInterface` _extends LaravelLengthAwarePaginatorContract_
  - `getAggregation(): array`

## src/Components/ElasticSearch/Queries

- class `MPS\Utils\Components\ElasticSearch\Queries\SearchQuery` _extends EnsiSearchQuery_
  - `__construct(SearchIndex $index)`
  - `setOptions(array $options): self`

## src/Components/ElasticSearch/QueryFilters

- class `MPS\Utils\Components\ElasticSearch\QueryFilters\IntFilter` _extends QueryFilter_
  - `setValue(mixed $value): self`
- class `MPS\Utils\Components\ElasticSearch\QueryFilters\NestedFilter` _extends QueryFilter_
  - `setValue(?Closure $value): self`
- class `MPS\Utils\Components\ElasticSearch\QueryFilters\NullableFilter` _extends QueryFilter_
  - `setValue(?bool $value): self`
- class `MPS\Utils\Components\ElasticSearch\QueryFilters\QueryFilter` _implements QueryFilterInterface_
  - `__construct()`
  - `apply(BoolQuery $query): void`
  - `setColumn(string $column): self`
  - `acceptNull(): self` — Разрешить null-значения.
- class `MPS\Utils\Components\ElasticSearch\QueryFilters\RangeFilter` _extends QueryFilter_
  - `setValueFrom(mixed $valueFrom): self`
  - `setValueTo(mixed $valueTo): self`
- class `MPS\Utils\Components\ElasticSearch\QueryFilters\StringFilter` _extends QueryFilter_
  - `setValue(mixed $value): self`

## src/Components/ElasticSearch/Repositories

- class `MPS\Utils\Components\ElasticSearch\Repositories\CRUDRepository` _extends Repository implements CRUDRepositoryInterface_
  - `create(CreateAction $action): array`
  - `update(UpdateAction $action): array`
  - `index(IndexAction $action): array`
  - `delete(DeleteAction $action): array`
  - `bulk(array $actions): array`
  - `search(array $params = [], int|array|string|null $id = null, int $perPage = 30, ?int $page = null, ?SearchQuery $query = null): LengthAwarePaginatorInterface|Collection|Document|null`
  - `oneById(int|string $id): ?Document`
  - `oneByIdOrFail(int|string $id, ?string $message = null): Document`
  - `oneByCondition(Closure $condition): ?Document`
  - `allByCondition(Closure $condition): Collection`
- class `MPS\Utils\Components\ElasticSearch\Repositories\Repository` _extends CoreRepository implements RepositoryInterface_
  - `getIndex(): SearchIndex`
  - `getIndexName(): string`
  - `initIndex(): bool`
  - `dropIndex(): bool`
  - `getColumns(): Collection`
  - `getPrimaryKeyName(): string`
  - `getQuery(): SearchQuery`

## src/Components/ElasticSearch/Services

- class `MPS\Utils\Components\ElasticSearch\Services\CRUDService` _extends CoreCRUDService implements CRUDServiceInterface_
  - `create(CreateCommandDataObject $dto): Document`
  - `searchByIds(array $ids, ?callable $callback = null, bool $keyBy = true): array`
  - `searchById(int|string $id, ?string $notFoundMessage = null): array`
  - `update(UpdateCommandDataObject $dto): bool`
  - `updateById(int|string $id, array $data): bool`
  - `delete(DeleteCommandDataObject $dto): bool`
  - `deleteById(int|string $id, bool $checkExistence = true): bool`

## src/Components/ElasticSearch/Traits

- trait `MPS\Utils\Components\ElasticSearch\Traits\ActionDataAwareTrait`
  - `getData(): array`
  - `setData(array $data): self`

## src/Components/Error

- class `MPS\Utils\Components\Error\MessageBag` _implements \IteratorAggregate_
  - `set(string $message): self`
  - `get(string $message): ?MessageBagItem`
  - `all(): array`
  - `count(): int`
  - `clear(): self`
  - `getIterator(): Traversable`
- trait `MPS\Utils\Components\Error\MessageBagAwareTrait`
  - `setMessageBag(MessageBag $messageBag): self`
  - `getMessageBag(): MessageBag`
- class `MPS\Utils\Components\Error\MessageBagItem` _extends DataObject_
  - `__construct(protected string $message)`
  - `hash(): ?string`
  - `message(): string`
  - `count(): int`
  - `increment(): self`
  - `decrement(): self`

## src/Components/Excel

- class `MPS\Utils\Components\Excel\ExcelReader`
  - `__construct(string $filePath)`
  - `setHeaderRowCount(int $headerRowCount): self`
  - `setHeaderRowsCallback(?Closure $closure): self`
  - `getHeaderRowsCallback(): ?Closure`
  - `getChunkSize(): int`
  - `setChunkSize(int $chunkSize): self`
  - `setRowLimit(int $value): self`
  - `setSkipHiddenSheets(?bool $value): self`
  - `setColumnKeysFormat(ColumnKeysFormatEnum $keysFormatEnum): self`
  - `getReader(): ReaderInterface`
  - `readRowsCallback(Closure $function, ?SheetInterface $sheet = null): int`
  - `readChunk(Closure $function, ?SheetInterface $sheet = null): bool`
- interface `MPS\Utils\Components\Excel\ReaderInterface`
  - `getSheet(?string $name = null): SheetInterface`
  - `getSheetNames(): array`
- interface `MPS\Utils\Components\Excel\SheetInterface`
  - `nextRow(int $rowLimit = 0): Generator`
  - `countRows(): int`
  - `isHidden(): bool`

## src/Components/Excel/Enums

- enum `MPS\Utils\Components\Excel\Enums\ColumnKeysFormatEnum` _: string implements Enumerable_
  - кейсы: `NUMERIC`, `LETTER`

## src/Components/Excel/Helpers

- class `MPS\Utils\Components\Excel\Helpers\ExcelColumnHelper`
  - `static formatRowKeys(array $row, ?ColumnKeysFormatEnum $keysFormatEnum): array`
  - `static toLetterKeys(array $row): array`
  - `static toNumericKeys(array $row): array`
  - `static letterToIndex(string $letter): int`
  - `static indexToLetter(int $index): string`

## src/Components/Excel/Xls

- class `MPS\Utils\Components\Excel\Xls\XlsReader` _implements ReaderInterface_
  - `__construct(string $filePath)`
  - `setColumnKeysFormat(ColumnKeysFormatEnum $value): self`
  - `getSheet(?string $name = null): SheetInterface`
  - `getSheetNames(): array`
- class `MPS\Utils\Components\Excel\Xls\XlsSheet` _implements SheetInterface_
  - `__construct(protected array $rows, protected bool $isHidden = false, protected ?ColumnKeysFormatEnum $columnKeysFormat = null)`
  - `nextRow(int $rowLimit = 0): Generator`
  - `countRows(): int`
  - `isHidden(): bool`

## src/Components/Excel/Xlsx

- class `MPS\Utils\Components\Excel\Xlsx\XlsxReader` _implements ReaderInterface_
  - `__construct(string $filePath)`
  - `setColumnKeysFormat(ColumnKeysFormatEnum $value): self`
  - `getSheet(?string $name = null): SheetInterface`
  - `getSheetNames(): array`
- class `MPS\Utils\Components\Excel\Xlsx\XlsxSheet` _implements SheetInterface_
  - `__construct(protected Sheet $sheet, protected ?ColumnKeysFormatEnum $columnKeysFormat = null)`
  - `nextRow(int $rowLimit = 0): Generator`
  - `countRows(): int`
  - `isHidden(): bool`

## src/Components/ExcelWriter

- class `MPS\Utils\Components\ExcelWriter\ExcelWriter` — TODO: объединить с ExcelReader и разместить в общей папке Excel.
  - `__construct(string $filePath, bool $toBrowser = false)`
  - `getCurrentSheet(): Sheet`
  - `addRows(GeneratorInterface $generator): self`
  - `addRow(Row|array|callable $row): self`
  - `addRowFromArray(array $values, ?Style $style = null): self`
  - `finish(): void`
  - `getStream()`

## src/Components/ExcelWriter/Helpers

- class `MPS\Utils\Components\ExcelWriter\Helpers\RowHelper`
  - `static normalize(Row|array|callable $row): Row`

## src/Components/ExcelWriter/Interfaces

- interface `MPS\Utils\Components\ExcelWriter\Interfaces\GeneratorInterface`
  - `generate(): Generator`

## src/Components/Exception/DataObjects

- class `MPS\Utils\Components\Exception\DataObjects\ExceptionContext` _extends DataObject implements DataObjectInterface_
  - `getException(): string`
  - `setException(string $exception): self`
  - `getMessage(): string`
  - `setMessage(string $message): self`
  - `getCode(): int|string`
  - `setCode(int|string $code): self`
  - `getStatusCode(): ?int`
  - `setStatusCode(?int $statusCode): self`
  - `getAppErrorCode(): AppErrorCodeEnumInterface|int|null`
  - `setAppErrorCode(AppErrorCodeEnumInterface|int|null $appErrorCode): self`
  - `getFile(): string`
  - `setFile(string $file): self`
  - `getLine(): int`
  - `setLine(int $line): self`
  - `getDetails(): array`
  - `setDetails(array $details): self`
  - `getTrace(): array`
  - `setTrace(array $trace): self`
  - `getPrevious(): ?array`
  - `setPrevious(?array $previous): self`

## src/Components/Exception/Factories

- class `MPS\Utils\Components\Exception\Factories\ExceptionContextFactory`
  - `static make(Throwable $e, bool $withTrace = false): ExceptionContext`

## src/Components/FileSystem

- class `MPS\Utils\Components\FileSystem\FileManager` _implements FileManagerInterface, LoggerAwareInterface_
  - `__construct(LoggerInterface $logger, FilesystemContract $storage, protected TmpFileManagerInterface $tmpFileManager)`
  - `get(string $path): string|null`
  - `has(string $path): bool`
  - `put(string $path, mixed $content, bool $isPublic = false, array $options = []): bool`
  - `uploadFromUrl(string $url, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `uploadFromBase64(string $base64, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `uploadFromContents(string|Closure $value, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `upload(UploadedFile $upload, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `getUrl(string $path): string`
  - `getTmpUrl(string $path, ?CarbonInterface $date = null): string`
  - `createTmpFile(string $path, string|UploadedFile $contents): ?string`
  - `deleteTmpFile(string $path): bool`
  - `getFileInfo(string $fullPath): FileInfo`
- class `MPS\Utils\Components\FileSystem\ServiceProvider` _extends ModuleServiceProvider_
  - `static getName(): string`
  - `register(): void`
- class `MPS\Utils\Components\FileSystem\StreamMetadataDetector` _implements StreamMetadataDetectorInterface_
  - `mimeType(mixed $stream): ?string`
  - `extension(mixed $stream): ?string`
- class `MPS\Utils\Components\FileSystem\TmpFileManager` _implements TmpFileManagerInterface_
  - `__construct()`
  - `put(string $path, string|UploadedFile $contents): ?string`
  - `delete(string $path): bool`
- class `MPS\Utils\Components\FileSystem\UploadManager` _implements LoggerAwareInterface, UploadManagerInterface_
  - `__construct(protected FileManagerInterface $fileManager, protected StreamMetadataDetectorInterface $metadataDetector, LoggerInterface $logger)`
  - `fromObject(UploadedFile $uploadFile, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromContents(string|Closure $value, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromBase64(string $base64, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromUrl(string $url, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromStream(mixed $stream, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `assertNameExtensionMatchesContent(?string $name, ?string $detectedExtension): void`

## src/Components/FileSystem/DataObjects

- class `MPS\Utils\Components\FileSystem\DataObjects\FileInfo` _extends DataObject implements NameAwareInterface, MimeTypeAwareInterface, ExtensionAwareInterface, SizeAwareInterface, PathAwareInterface_
- class `MPS\Utils\Components\FileSystem\DataObjects\UploadInfo` _extends FileInfo_
  - `getHash(): string`
  - `setHash(string $value): self`
  - `getOriginExtension(): string`
  - `setOriginExtension(string $value): self`

## src/Components/FileSystem/Factories

- class `MPS\Utils\Components\FileSystem\Factories\FileSystemFactory`
  - `static makeLocalStorage(string $path): FilesystemContract`
  - `static makeStorage(string $path, string $driver, array $config = []): FilesystemContract`

## src/Components/FileSystem/Helpers

- class `MPS\Utils\Components\FileSystem\Helpers\FileMimeTypeHelper`
  - `static areCompatible(string $provided, string $detected): bool`
  - `static getPrimaryForExtension(string $extension): ?string`
  - `static getAllForExtension(string $extension): array`
  - `static getExtensionsByMime(string $mime): array`
- class `MPS\Utils\Components\FileSystem\Helpers\FileSystemHelper`
  - `static normalizeFileName(?string $value = null): string`
  - `static extractExtension(string $value): string`
  - `static normalizePath(string $value): string`
  - `static generateUniqueName(): string`
  - `static buildPath(string $dir, ?string $name = null, ?string $extension = null): string`

## src/Components/FileSystem/Interfaces

- interface `MPS\Utils\Components\FileSystem\Interfaces\ExtensionAwareInterface`
  - `getExtension(): string`
  - `setExtension(string $value): self`
- interface `MPS\Utils\Components\FileSystem\Interfaces\FileManagerInterface` _extends StorageAwareInterface_
  - `get(string $path): string|null`
  - `has(string $path): bool`
  - `put(string $path, mixed $content, bool $isPublic = false, array $options = []): bool`
  - `uploadFromUrl(string $url, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `uploadFromBase64(string $base64, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `uploadFromContents(string|\Closure $value, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `upload(UploadedFile $upload, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `getUrl(string $path): string`
  - `getTmpUrl(string $path): string`
  - `createTmpFile(string $path, string|UploadedFile $contents): ?string`
  - `deleteTmpFile(string $path): bool`
  - `getFileInfo(string $fullPath): FileInfo`
- interface `MPS\Utils\Components\FileSystem\Interfaces\MimeTypeAwareInterface`
  - `getMimeType(): string`
  - `setMimeType(string $value): self`
- interface `MPS\Utils\Components\FileSystem\Interfaces\PathAwareInterface`
  - `getPath(): string`
  - `setPath(string $value): self`
- interface `MPS\Utils\Components\FileSystem\Interfaces\SizeAwareInterface`
  - `getSize(): int`
  - `setSize(int $value): self`
- interface `MPS\Utils\Components\FileSystem\Interfaces\StorageAwareInterface`
  - `setStorage(FilesystemContract $storage): static`
  - `getStorage(): FilesystemContract`
- interface `MPS\Utils\Components\FileSystem\Interfaces\StreamMetadataDetectorInterface`
  - `mimeType(mixed $stream): ?string`
  - `extension(mixed $stream): ?string`
- interface `MPS\Utils\Components\FileSystem\Interfaces\TmpFileManagerInterface` _extends StorageAwareInterface_
  - `put(string $path, string|UploadedFile $contents): ?string`
  - `delete(string $path): bool`
- interface `MPS\Utils\Components\FileSystem\Interfaces\UploadManagerInterface`
  - `fromObject(UploadedFile $uploadFile, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromContents(string|Closure $value, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromBase64(string $base64, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromUrl(string $url, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `fromStream($stream, string $dir, ?string $name = null, array $options = []): ?UploadInfo`
  - `assertNameExtensionMatchesContent(?string $name, ?string $detectedExtension): void`

## src/Components/FileSystem/Traits

- trait `MPS\Utils\Components\FileSystem\Traits\ExtensionAwareTrait`
  - `getExtension(): string`
  - `setExtension(string $value): self`
- trait `MPS\Utils\Components\FileSystem\Traits\MimeTypeAwareTrait`
  - `getMimeType(): string`
  - `setMimeType(string $value): self`
- trait `MPS\Utils\Components\FileSystem\Traits\PathAwareTrait`
  - `getPath(): string`
  - `setPath(string $value): self`
- trait `MPS\Utils\Components\FileSystem\Traits\SizeAwareTrait`
  - `getSize(): int`
  - `setSize(int $value): self`
- trait `MPS\Utils\Components\FileSystem\Traits\StorageAwareTrait`
  - `setStorage(FilesystemContract $storage): static`
  - `getStorage(): FilesystemContract`

## src/Components/HealthCheck

- class `MPS\Utils\Components\HealthCheck\HealthCheckManager` _extends Service implements HealthCheckManagerInterface, LoggerAwareInterface_
  - `__construct(LoggerInterface $logger)`
  - `liveness(): array` — Проверка работоспособности приложения
  - `readiness(): array` — Проверка готовности компонентов приложения
- class `MPS\Utils\Components\HealthCheck\ServiceProvider` _extends ModuleServiceProvider_
  - `static getName(): string`
  - `getDir(): string`
  - `loadRoutes(): bool`

## src/Components/HealthCheck/Console

- class `MPS\Utils\Components\HealthCheck\Console\Command` _extends LaravelCommand implements LoggerAwareInterface_
  - `handle(HealthCheckManagerInterface $service, LoggerInterface $logger): void`
- class `MPS\Utils\Components\HealthCheck\Console\LivenessCommand` _extends Command_
- class `MPS\Utils\Components\HealthCheck\Console\ReadinessCommand` _extends Command_

## src/Components/HealthCheck/Enums

- enum `MPS\Utils\Components\HealthCheck\Enums\ComponentStateEnum` _: string implements Enumerable_
  - кейсы: `OK`, `ERROR`
- enum `MPS\Utils\Components\HealthCheck\Enums\ComponentsEnum` _: string implements ComponentsEnumInterface_
  - кейсы: `DATABASES`, `CACHE`, `QUEUE`

## src/Components/HealthCheck/Http/Actions

- class `MPS\Utils\Components\HealthCheck\Http\Actions\Action` _extends CoreAction_
  - `__construct(HealthCheckManagerInterface $service)`
- class `MPS\Utils\Components\HealthCheck\Http\Actions\LivenessAction` _extends Action_
  - `run(): JsonResponse`
- class `MPS\Utils\Components\HealthCheck\Http\Actions\ReadinessAction` _extends Action_
  - `run(): JsonResponse`

## src/Components/HealthCheck/Http/Controllers

- class `MPS\Utils\Components\HealthCheck\Http\Controllers\HealthCheckController` _extends CoreController_ — Контроллер проверки работоспособности микросервиса и его компонентов
  - `actions(): array`

## src/Components/HealthCheck/Http/Responses

- class `MPS\Utils\Components\HealthCheck\Http\Responses\LivenessResponse` _extends JsonResponse_
- class `MPS\Utils\Components\HealthCheck\Http\Responses\ReadinessResponse` _extends JsonResponse_

## src/Components/HealthCheck/Interfaces

- interface `MPS\Utils\Components\HealthCheck\Interfaces\ComponentsEnumInterface` _extends \BackedEnum, Enumerable_
- interface `MPS\Utils\Components\HealthCheck\Interfaces\HealthCheckManagerInterface` _extends ServiceInterface_
  - `liveness(): array`
  - `readiness(): array`

## src/Components/Http

- class `MPS\Utils\Components\Http\HttpClient` _implements HttpClientInterface_
  - `__construct(array $config = [])`
  - `getConfig(): HttpClientConfig`
  - `setConfig(HttpClientConfig $config): self`
  - `getRequest(): PendingRequest`
  - `setRequest(PendingRequest $request): self`
  - `freshRequest(): PendingRequest`
  - `get(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `post(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `put(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `patch(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `delete(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `request(HttpRequest $request, ?Closure $callback = null): Response|PromiseInterface`
  - `concurrent(Closure $action, bool $unWrap = true): array`

## src/Components/Http/Components/ConcurrentRequest

- class `MPS\Utils\Components\Http\Components\ConcurrentRequest\ConcurrentRequestManager` _implements ConcurrentRequestManagerInterface_
  - `__construct(LoggerInterface $logger)`
  - `pushRequest(HttpRequest $dataObject): self`
  - `getRequestMap(): array`
  - `freshRequestMap(): self`
  - `isScan(): bool`
  - `execute(Closure $action, bool $unWrap = true): array`
  - `unwrap(array $promises): array`

## src/Components/Http/Components/ConcurrentRequest/DataObjects

- class `MPS\Utils\Components\Http\Components\ConcurrentRequest\DataObjects\ConcurrentResponse` _extends HttpRequest_
  - `getResponse(): mixed`
  - `setResponse(mixed $value): self`

## src/Components/Http/Components/ConcurrentRequest/Interfaces

- interface `MPS\Utils\Components\Http\Components\ConcurrentRequest\Interfaces\ConcurrentRequestManagerInterface` _extends MakeAwareInterface, LoggerAwareInterface_
  - `pushRequest(HttpRequest $dataObject): self`
  - `getRequestMap(): array`
  - `freshRequestMap(): self`
  - `isScan(): bool`
  - `execute(Closure $action, bool $unWrap = true): array`
  - `unwrap(array $promises): array`

## src/Components/Http/DataObjects

- class `MPS\Utils\Components\Http\DataObjects\HttpClientConfig` _extends DataObject_
  - `getRetry(): ?int`
  - `setRetry(int $retry): self`
  - `isCatchException(): bool`
  - `setCatchException(bool $catchException): self`
  - `getTimeOut(): ?int`
  - `setTimeOut(int $timeOut): self`
  - `getConnectTimeout(): ?int`
  - `setConnectTimeout(int $connectTimeout): self`
- class `MPS\Utils\Components\Http\DataObjects\HttpRequest` _extends DataObject_
  - `getMethod(): HttpRequestTypeEnum`
  - `setMethod(string|HttpRequestTypeEnum $value): self`
  - `getUrl(): string`
  - `setUrl(string $value): self`
  - `getData(): array`
  - `setData(array $values): self`
  - `getRequestInstance(): PendingRequest`
  - `setRequestInstance(PendingRequest $value): self`

## src/Components/Http/Enums

- enum `MPS\Utils\Components\Http\Enums\HttpRequestTypeEnum` _: string implements Enumerable_
  - кейсы: `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `PATCH`, `HEAD`

## src/Components/Http/Events

- class `MPS\Utils\Components\Http\Events\AfterClientRequestEvent` _extends ClientRequestEvent_
- class `MPS\Utils\Components\Http\Events\BeforeClientRequestEvent` _extends ClientRequestEvent_
- class `MPS\Utils\Components\Http\Events\ClientRequestEvent` _extends CoreEvent implements ClientRequestEventInterface_
  - `__construct(protected HttpRequest $request)`
  - `getRequest(): HttpRequest`

## src/Components/Http/Factories

- class `MPS\Utils\Components\Http\Factories\HttpClientFactory`
  - `static makeClient(array $config = []): HttpClient`
  - `static makeClientConfig(array $data = []): HttpClientConfig`
  - `static makeRequest(string|HttpRequestTypeEnum $method, string $url, array $data = []): HttpRequest`

## src/Components/Http/HttpRequestBuilder

- class `MPS\Utils\Components\Http\HttpRequestBuilder\HttpClient`
  - `__construct(?LoggerInterface $logger = null)`
  - `getConfig(): HttpClientConfig`
  - `setConfig(HttpClientConfig $value): self`
  - `getRequest(): PendingRequest`
  - `setRequest(PendingRequest $value): self`
  - `setLogger(LoggerInterface $logger): self`
  - `freshRequest(): PendingRequest`
  - `get(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `post(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `put(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `patch(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `delete(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `request(HttpRequest $request, ?Closure $callback = null): Response|PromiseInterface`
- class `MPS\Utils\Components\Http\HttpRequestBuilder\HttpConcurrentBuilder`
  - `static make(): self`
  - `withConfig(HttpClientConfig $config): self`
  - `withHeader(string $name, string $value): self`
  - `withHeaders(array $headers): self`
  - `withLogger(LoggerInterface $logger): self`
  - `add(string $alias, HttpRequestBuilder $request): self`
  - `send(): array`
- class `MPS\Utils\Components\Http\HttpRequestBuilder\HttpRequestBuilder`
  - `static get(string $url): self`
  - `static post(string $url): self`
  - `static put(string $url): self`
  - `static patch(string $url): self`
  - `static delete(string $url): self`
  - `withConfig(HttpClientConfig $config): self`
  - `withHeader(string $name, string $value): self`
  - `withHeaders(array $headers): self`
  - `withQueryParams(array $params): self`
  - `withBody(array $data): self`
  - `withCallback(Closure $callback): self`
  - `withLogger(LoggerInterface $logger): self`
  - `send(): Response`
  - `sendWith(HttpClient $client): Response|PromiseInterface`

## src/Components/Http/HttpRequestBuilder/DataObjects

- class `MPS\Utils\Components\Http\HttpRequestBuilder\DataObjects\HttpClientConfig` _extends DataObject_
  - `getRetry(): ?int`
  - `setRetry(int $value): self`
  - `isCatchException(): bool`
  - `setCatchException(bool $value): self`
  - `getTimeOut(): ?int`
  - `setTimeOut(int $value): self`
  - `getConnectTimeout(): ?int`
  - `setConnectTimeout(int $value): self`
- class `MPS\Utils\Components\Http\HttpRequestBuilder\DataObjects\HttpRequest` _extends DataObject_
  - `getMethod(): HttpRequestTypeEnum`
  - `setMethod(string|HttpRequestTypeEnum $value): self`
  - `getUrl(): string`
  - `setUrl(string $value): self`
  - `getData(): array`
  - `setData(array $values): self`
  - `getRequestInstance(): PendingRequest`
  - `setRequestInstance(PendingRequest $value): self`

## src/Components/Http/HttpRequestBuilder/Enums

- enum `MPS\Utils\Components\Http\HttpRequestBuilder\Enums\HttpRequestTypeEnum` _: string implements Enumerable_
  - кейсы: `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `PATCH`, `HEAD`

## src/Components/Http/Interfaces

- interface `MPS\Utils\Components\Http\Interfaces\ClientRequestEventInterface` _extends MakeAwareInterface_
- interface `MPS\Utils\Components\Http\Interfaces\HttpClientInterface`
  - `getConfig(): HttpClientConfig`
  - `setConfig(HttpClientConfig $config): self`
  - `getRequest(): PendingRequest`
  - `setRequest(PendingRequest $request): self`
  - `freshRequest(): PendingRequest`
  - `get(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `post(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `put(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `patch(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `delete(string $url, array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `request(HttpRequest $request, ?Closure $callback = null): Response|PromiseInterface`
  - `concurrent(Closure $action, bool $unWrap = true): array`
- interface `MPS\Utils\Components\Http\Interfaces\HttpClientResponseInterface`
- interface `MPS\Utils\Components\Http\Interfaces\HttpRepositoryInterface`
  - `getClient(): HttpClient`
  - `request(HttpRequestTypeEnum $requestType, string $extendUrl = '', array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `concurrent(Closure $action): array`
  - `extract(Response|PromiseInterface $response, bool $unPack = false): Response|PromiseInterface|array`

## src/Components/Http/Providers

- class `MPS\Utils\Components\Http\Providers\EventServiceProvider` _extends LaravelEventServiceProvider_
  - `__construct($dispatcher = null)`
- class `MPS\Utils\Components\Http\Providers\ServiceProvider` _extends CoreServiceProvider_
  - `register(): void`

## src/Components/Http/Repository

- class `MPS\Utils\Components\Http\Repository\HttpRepository` _implements HttpRepositoryInterface_
  - `__construct()`
  - `getClient(): HttpClient`
  - `__call(string $name, array $arguments): Response|PromiseInterface`
  - `request(HttpRequestTypeEnum $requestType, string $extendUrl = '', array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `concurrent(Closure $action): array`
  - `extract(Response|PromiseInterface $response, bool $unPack = true): Response|PromiseInterface|array`

## src/Components/Http/Responses

- class `MPS\Utils\Components\Http\Responses\HttpClientErrorResponse` _implements HttpClientResponseInterface_
  - `__construct(protected \Throwable $exception)`
  - `getException(): \Throwable`
- class `MPS\Utils\Components\Http\Responses\HttpClientSuccessResponse` _implements HttpClientResponseInterface_
  - `__construct(protected IlluminateHttpResponse $response)`
  - `getResponse(): IlluminateHttpResponse`

## src/Components/Integration/ServiceBus/DataObjects

- class `MPS\Utils\Components\Integration\ServiceBus\DataObjects\ErrorInfo` _extends DataObject_
  - `getStatus(): int`
  - `setStatus(int $value): self`
  - `getError(): string`
  - `setError(string $value): self`
  - `getAppErrorCode(): int`
  - `setAppErrorCode(int $value): self`
  - `getMessage(): string`
  - `setMessage(string $value): self`
  - `getHostname(): string`
  - `setHostname(string $value): self`
  - `getDetails(): array`
  - `setDetails(array $values): self`
  - `getFile(): string`
  - `setFile(string $value): self`
  - `getTrace(): array`
  - `setTrace(array $values): self`
- class `MPS\Utils\Components\Integration\ServiceBus\DataObjects\ErrorResponse` _extends ServicesResponse_
  - `__construct()`
  - `getData(): ErrorInfo`
  - `setData(array|ErrorInfo $data): self`
- class `MPS\Utils\Components\Integration\ServiceBus\DataObjects\ServicesResponse` _extends DataObject_
  - `isSuccess(): bool`
  - `setSuccess(bool $value): self`
- class `MPS\Utils\Components\Integration\ServiceBus\DataObjects\SuccessResponse` _extends ServicesResponse_
  - `getData(): array`
  - `setData(array $data): self`

## src/Components/Integration/ServiceBus/Enums

- enum `MPS\Utils\Components\Integration\ServiceBus\Enums\ServiceBusHeaderEnum` _: string implements Enumerable_
  - кейсы: `AUTH_KEY`, `STRESS_TEST_ENABLED`
  - `static labels(): array`
  - `static proxied(): array`

## src/Components/Integration/ServiceBus/Repositories

- class `MPS\Utils\Components\Integration\ServiceBus\Repositories\CRUDRepository` _extends Repository implements CRUDRepositoryInterface_
  - `create(array $data): SuccessResponse|PromiseInterface`
  - `paginate(array $params = []): SuccessResponse|PromiseInterface`
  - `getById(int|string $id): SuccessResponse|PromiseInterface`
  - `updateById(int|string $id, array $data): SuccessResponse|PromiseInterface`
  - `deleteById(int|string $id): void`
- interface `MPS\Utils\Components\Integration\ServiceBus\Repositories\CRUDRepositoryInterface` _extends RepositoryInterface_
  - `create(array $data): SuccessResponse|PromiseInterface`
  - `paginate(array $params = []): SuccessResponse|PromiseInterface`
  - `getById(int|string $id): SuccessResponse|PromiseInterface`
  - `updateById(int|string $id, array $data): SuccessResponse|PromiseInterface`
  - `deleteById(int|string $id): void`
- class `MPS\Utils\Components\Integration\ServiceBus\Repositories\Repository` _extends HttpRepository implements RepositoryInterface_
  - `request(HttpRequestTypeEnum $requestType, string $extendUrl = '', array $data = [], ?Closure $callback = null): Response|PromiseInterface`
  - `concurrent(Closure $action): array`
- interface `MPS\Utils\Components\Integration\ServiceBus\Repositories\RepositoryInterface` _extends HttpRepositoryInterface_

## src/Components/Integration/ServiceBus/Services

- class `MPS\Utils\Components\Integration\ServiceBus\Services\CRUDService` _extends CoreCRUDService_
  - `create(CreateCommandDataObject $dto): mixed`
  - `search(SearchDataObject $dto, ?callable $callback = null, bool $asArray = true): array`
  - `searchByIds(array $ids, ?callable $callback = null, bool $keyBy = true): array`
  - `searchById(int|string $id, ?string $notFoundMessage = null): array`
  - `update(UpdateCommandDataObject $dto): bool`
  - `updateById(int|string $id, array $data): bool`
  - `delete(DeleteCommandDataObject $dto): bool`
  - `deleteById(int|string $id, bool $checkExistence = true): bool`

## src/Components/ItemsContainer

- class `MPS\Utils\Components\ItemsContainer\ItemsContainer` _extends DataObject implements ItemsContainerAwareInterface_
- interface `MPS\Utils\Components\ItemsContainer\ItemsContainerAwareInterface` _extends IteratorAggregate_
  - `getItems(): array`
  - `getIds(): array`
  - `clear(): bool`
  - `getIterator(): \ArrayIterator`
- trait `MPS\Utils\Components\ItemsContainer\ItemsContainerAwareTrait`
  - `getItems(): array`
  - `getIds(): array`
  - `getIterator(): \ArrayIterator`
  - `clear(): bool`

## src/Components/Jwt

- class `MPS\Utils\Components\Jwt\JwtManager`
  - `__construct(protected readonly JwtSignerInterface $signer)`
  - `generate(string $secret, int $ttl = 3600, array $payload = []): string`
  - `validate(string $token, string $secret): bool`

## src/Components/Jwt/Contracts

- interface `MPS\Utils\Components\Jwt\Contracts\JwtSignerInterface`
  - `sign(string $data, string $secret): string`
  - `verify(string $data, string $signature, string $secret): bool`
  - `algorithm(): string`

## src/Components/Jwt/Signers

- class `MPS\Utils\Components\Jwt\Signers\HS256Signer` _implements JwtSignerInterface_
  - `sign(string $data, string $secret): string`
  - `verify(string $data, string $signature, string $secret): bool`
  - `algorithm(): string`

## src/Components/Logger

- class `MPS\Utils\Components\Logger\CustomizeFormatter`
  - `__invoke(Logger $logger)`
- class `MPS\Utils\Components\Logger\LogContext`
  - `static set(string $key, mixed $value): void`
  - `static push(array $context): void`
  - `static all(): array`
  - `static clear(): void`
- class `MPS\Utils\Components\Logger\LogManager` _extends IlluminateLogManager implements UtilsLoggerInterface_
  - `__construct(Application $app)`
  - `channel($channel = null): UtilsLoggerInterface` — Переопределяет метод базового LogManager, уточняя возвращаемый тип для соответствия нашему кастомному интерфейсу.
  - `driver($driver = null): UtilsLoggerInterface` — Переопределяет метод базового LogManager, уточняя возвращаемый тип для соответствия нашему кастомному интерфейсу.
- class `MPS\Utils\Components\Logger\LogServiceProvider` _extends ServiceProvider_
  - `register()`
  - `boot()`
- class `MPS\Utils\Components\Logger\LogStorage` _implements LogStorageInterface_
  - `__construct(Request $request)` — LogStorage constructor.
  - `setServiceName(string $serviceName): void`
  - `getServiceName(): string`
  - `getCurrentServiceName(): string`
  - `addLog(MessageLogged $event): bool`
  - `addLogByKey(string $key, MessageLogged $event): bool`
  - `setGeneral(array $data): void`
  - `addGeneralByKey(string $key, $value): void`
  - `all(): array`
- class `MPS\Utils\Components\Logger\Logger` _extends IlluminateLogger implements Contextable, LoggerInterface_
- class `MPS\Utils\Components\Logger\LoggerFactory`
  - `static makeLogger(PsrLoggerInterface $logger, ?Dispatcher $dispatcher = null): Logger`
  - `static makeStreamHandler($stream, int|string|Level $level = Level::Debug): MonologStreamHandler`
- class `MPS\Utils\Components\Logger\RuntimeMetadata`
  - `__construct()`
  - `set(object $object, string $key, mixed $value): void`
  - `get(object $object, string $key): mixed`

## src/Components/Logger/Components/Monolog/Handler

- class `MPS\Utils\Components\Logger\Components\Monolog\Handler\StreamHandler` _extends MonologStreamHandler_
  - `handle($record): bool`

## src/Components/Logger/Enums

- class `MPS\Utils\Components\Logger\Enums\LogHeaderEnum` _extends Enum_
  - константы: `LOG_TRACE`

## src/Components/Logger/Http

- class `MPS\Utils\Components\Logger\Http\LoggingMiddleware`
  - `handle(Request $request, Closure $next): mixed`

## src/Components/Logger/Interfaces

- interface `MPS\Utils\Components\Logger\Interfaces\LogStorageInterface`
  - `setServiceName(string $serviceName): void`
  - `getServiceName(): string`
  - `getCurrentServiceName(): string`
  - `addLog(MessageLogged $event): bool`
  - `addLogByKey(string $key, MessageLogged $event): bool`
  - `setGeneral(array $data): void`
  - `addGeneralByKey(string $key, $value): void`
  - `all(): array`
- interface `MPS\Utils\Components\Logger\Interfaces\LoggerAwareInterface`
  - `setLogger(LoggerInterface $logger): static`
  - `getLogger(): LoggerInterface`
- interface `MPS\Utils\Components\Logger\Interfaces\LoggerInterface` _extends PsrLoggerInterface_
  - `for(object $caller): static`
  - `setPrefix(string $value): static`
  - `getPrefix(): string`
  - `setMessagePrefix(string $prefix): static`
  - `errorException(Throwable $e, bool $withTrace = false, array $extraContext = []): void`
  - `exception(string $level, Throwable $e, bool $withTrace = false, array $extraContext = []): void`

## src/Components/Logger/Traits

- trait `MPS\Utils\Components\Logger\Traits\LoggerAwareTrait`
  - `setLogger(LoggerInterface $logger): static`
  - `getLogger(): LoggerInterface`
- trait `MPS\Utils\Components\Logger\Traits\LoggerTrait`
  - `for(object $caller): static`
  - `setPrefix(string $value): static`
  - `getPrefix(): string`
  - `setMessagePrefix(string $value): static`
  - `emergency($message, array $context = []): void` — System is unusable.
  - `alert($message, array $context = []): void` — Action must be taken immediately.
  - `critical($message, array $context = []): void` — Critical conditions.
  - `error($message, array $context = []): void` — Runtime errors that do not require immediate action but should typically be logged and monitored.
  - `warning($message, array $context = []): void` — Exceptional occurrences that are not errors.
  - `notice($message, array $context = []): void` — Normal but significant events.
  - `info($message, array $context = []): void` — Interesting events.
  - `debug($message, array $context = []): void` — Detailed debug information.
  - `log($level, $message, array $context = []): void` — Logs with an arbitrary level.
  - `errorException(Throwable $e, bool $withTrace = false, array $extraContext = []): void`
  - `exception(string $level, Throwable $e, bool $withTrace = false, array $extraContext = []): void`

## src/Components/MultiLanguage/Models

- interface `MPS\Utils\Components\MultiLanguage\Models\TranslationModelInterface`
- trait `MPS\Utils\Components\MultiLanguage\Models\TranslationModelTrait`
  - `translation(): HasMany`

## src/Components/MultiLanguage/Repositories

- class `MPS\Utils\Components\MultiLanguage\Repositories\TranslationEntityRepository` _extends CRUDRepository implements TranslationEntityRepositoryInterface_
- interface `MPS\Utils\Components\MultiLanguage\Repositories\TranslationEntityRepositoryInterface` _extends CRUDRepositoryInterface_
  - `translationRepository(): TranslationRepositoryInterface`
  - `translationTableName(): string`
  - `translatableAttributes(): array`
- interface `MPS\Utils\Components\MultiLanguage\Repositories\TranslationRepositoryInterface` _extends CRUDRepositoryInterface_

## src/Components/MultiLanguage/Requests

- class `MPS\Utils\Components\MultiLanguage\Requests\TranslatableRequest` _extends CommandRequest_
  - `rules(): array`
  - `attributes(): array`

## src/Components/MultiLanguage/Services

- interface `MPS\Utils\Components\MultiLanguage\Services\TranslatableServiceInterface`
  - `translationRepository(): TranslationRepositoryInterface`
  - `translationSearch(SearchDataObject $dto): void`
  - `translationUpsert(CommandDataObject $dto, ?int $entityId = null): bool`
- trait `MPS\Utils\Components\MultiLanguage\Services\TranslatableServiceTrait`
  - `getRepository(): TranslationEntityRepositoryInterface`
  - `translationRepository(): TranslationRepositoryInterface`
  - `translationSearch(SearchDataObject $dto): void`
  - `translationUpsert(CommandDataObject $dto, ?int $entityId = null): bool`

## src/Components/MultiLanguage/Traits

- trait `MPS\Utils\Components\MultiLanguage\Traits\TranslationEntityRepositoryTrait`
  - `translationTableName(): string`
  - `translatableAttributes(): array`

## src/Components/Prometheus

- class `MPS\Utils\Components\Prometheus\MetricManager` _implements MetricManagerInterface_
  - `__construct(protected RegistryInterface $registry, protected array $httpCollectors)`
  - `recordRequest(Request $request, Response $response): void`
  - `export(): string`
  - `wipe(): void`
  - `getRegistry(): RegistryInterface`
- class `MPS\Utils\Components\Prometheus\Registry` _implements RegistryInterface_
  - `__construct(protected PrometheusRegistryInterface $decorated)`
  - `wipeStorage(): void`
  - `getMetricFamilySamples(): array`
  - `getOrRegisterGauge(GaugeMetric $dataObject): Gauge`
  - `getOrRegisterCounter(CounterMetric $dataObject): Counter`
  - `getOrRegisterHistogram(HistogramMetric $dataObject): Histogram`
  - `getOrRegisterSummary(SummaryMetric $dataObject): Summary`
- class `MPS\Utils\Components\Prometheus\ServiceProvider` _extends ModuleServiceProvider_
  - `getDir(): string`
  - `boot(): void`

## src/Components/Prometheus/Collectors

- class `MPS\Utils\Components\Prometheus\Collectors\BaseHttpCollector` _implements HttpCollectorInterface_
- class `MPS\Utils\Components\Prometheus\Collectors\RequestDurationCollector` _extends BaseHttpCollector_
  - `collect(RegistryInterface $registry, Request $request, Response $response): void`
- class `MPS\Utils\Components\Prometheus\Collectors\RequestTotalCollector` _extends BaseHttpCollector_
  - `collect(RegistryInterface $registry, Request $request, Response $response): void`

## src/Components/Prometheus/Console

- class `MPS\Utils\Components\Prometheus\Console\WipeStorageCommand` _extends Command_
  - `handle(MetricManagerInterface $metricManager): int` — Execute the console command.

## src/Components/Prometheus/DataObjects

- class `MPS\Utils\Components\Prometheus\DataObjects\CounterMetric` _extends MetricType_
- class `MPS\Utils\Components\Prometheus\DataObjects\GaugeMetric` _extends MetricType_
- class `MPS\Utils\Components\Prometheus\DataObjects\HistogramMetric` _extends MetricType_
  - `getBuckets(): array`
  - `setBuckets(array $items): self`
- class `MPS\Utils\Components\Prometheus\DataObjects\MetricType` _extends DataObject_
  - `getNamespace(): string`
  - `setNamespace(string $value): self`
  - `getName(): MetricNameInterface`
  - `setName(MetricNameInterface $value): self`
  - `getHelp(): string`
  - `setHelp(string $value): self`
  - `getLabels(): array`
  - `setLabels(array $items): self`
- class `MPS\Utils\Components\Prometheus\DataObjects\SummaryMetric` _extends MetricType_
  - `getMaxAgeSeconds(): int`
  - `setMaxAgeSeconds(int $value): self`
  - `getQuantiles(): array`
  - `setQuantiles(array $items): self`

## src/Components/Prometheus/Enums

- enum `MPS\Utils\Components\Prometheus\Enums\HttpMetricEnum` _: string implements MetricNameInterface_
  - кейсы: `REQUEST_DURATION_SECONDS`, `REQUESTS_TOTAL`
  - `getTitle(): string`

## src/Components/Prometheus/Http/Controllers

- class `MPS\Utils\Components\Prometheus\Http\Controllers\MetricController` _extends Controller_
  - `__construct(protected readonly MetricManagerInterface $manager)`
  - `export(): Response` — Выводит prometheus метрики

## src/Components/Prometheus/Http/Middleware

- class `MPS\Utils\Components\Prometheus\Http\Middleware\MetricMiddleware` — Middleware собирает метрики Prometheus для HTTP-запросов
  - `__construct(protected MetricManagerInterface $metricManager)`
  - `handle(Request $request, \Closure $next): mixed`
  - `terminate(Request $request, Response $response): void` — Записывает в метрики время выполнения запроса после отправки ответа клиенту

## src/Components/Prometheus/Interfaces

- interface `MPS\Utils\Components\Prometheus\Interfaces\HttpCollectorInterface`
  - `collect(RegistryInterface $registry, Request $request, Response $response): void`
- interface `MPS\Utils\Components\Prometheus\Interfaces\MetricManagerInterface`
  - `recordRequest(Request $request, Response $response): void`
  - `export(): string`
  - `wipe(): void`
  - `getRegistry(): RegistryInterface`
- interface `MPS\Utils\Components\Prometheus\Interfaces\MetricNameInterface`
  - `getTitle(): string`
- interface `MPS\Utils\Components\Prometheus\Interfaces\RegistryInterface`
  - `wipeStorage(): void`
  - `getMetricFamilySamples(): array`
  - `getOrRegisterGauge(GaugeMetric $dataObject): Gauge`
  - `getOrRegisterCounter(CounterMetric $dataObject): Counter`
  - `getOrRegisterHistogram(HistogramMetric $dataObject): Histogram`
  - `getOrRegisterSummary(SummaryMetric $dataObject): Summary`

## src/Components/QueryFilters

- class `MPS\Utils\Components\QueryFilters\IntFilter` _extends QueryFilter_
  - `apply(mixed $value): bool`
- class `MPS\Utils\Components\QueryFilters\QueryFilter` _implements QueryFilterInterface_
  - `__construct(protected BuilderContract $query, protected string $tableName, protected string $columnName)`
  - `apply(mixed $value): bool`
- class `MPS\Utils\Components\QueryFilters\StringFilter` _extends QueryFilter_
  - `setMatchMode(FilterMatchEnum $value): self`
  - `setCaseSensitive(bool $value): self`
  - `apply(mixed $value): bool`

## src/Components/QueryFilters/Enums

- enum `MPS\Utils\Components\QueryFilters\Enums\FilterMatchEnum` _: int_
  - кейсы: `FULL_MATCH`, `FROM_START_MATCH`, `PART_MATCH`

## src/Components/QueryFilters/Interfaces

- interface `MPS\Utils\Components\QueryFilters\Interfaces\QueryFilterInterface`
  - `apply(mixed $value): bool`

## src/Components/QueryFilters/V2

- class `MPS\Utils\Components\QueryFilters\V2\IntFilter` _extends QueryFilter_
  - `setValue(mixed $value): self`
- class `MPS\Utils\Components\QueryFilters\V2\NullableFilter` _extends QueryFilter_
  - `setValue(?bool $value): self`
- class `MPS\Utils\Components\QueryFilters\V2\QueryFilter` _implements QueryFilterInterface_
  - `__construct()`
  - `apply(BuilderContract $query): void`
  - `setColumn(string|Expression $column): self`
  - `acceptNull(): self` — Разрешить null-значения.
- class `MPS\Utils\Components\QueryFilters\V2\RangeFilter` _extends QueryFilter_
  - `setValueFrom(mixed $valueFrom): self`
  - `setValueTo(mixed $valueTo): self`
- class `MPS\Utils\Components\QueryFilters\V2\StringFilter` _extends QueryFilter_
  - `setValue(mixed $value): self`
  - `setMatchMode(FilterMatchEnum $value): self`
  - `setCaseSensitive(bool $value): self`

## src/Components/QueryFilters/V2/Interfaces

- interface `MPS\Utils\Components\QueryFilters\V2\Interfaces\QueryFilterInterface`
  - `apply(BuilderContract $query): void`

## src/Components/Queue/RabbitMQ

- class `MPS\Utils\Components\Queue\RabbitMQ\RabbitManager` _implements RabbitManagerInterface_
  - `putIn(array|DataObjectInterface $payload, string $queueName, string $exchange = '', bool $durable = true, bool $persistent = true): void`
  - `basicPublish(RabbitMessage $message): void`
  - `batchBasicPublish(RabbitMessage $message): void`
- class `MPS\Utils\Components\Queue\RabbitMQ\ServiceProvider` _extends ModuleServiceProvider_
  - `static getName(): string`
  - `getDir(): string`

## src/Components/Queue/RabbitMQ/Console

- class `MPS\Utils\Components\Queue\RabbitMQ\Console\QueueCleanupCommand` _extends QueueCommand_
  - `extendHandle(): void`
- class `MPS\Utils\Components\Queue\RabbitMQ\Console\QueueCommand` _extends Command_
  - `handle(QueueManagerInterface $queueManager, DelayedQueueManagerInterface $delayedQueueManager, AlternateExchangeManager $alternateExchangeManager): void`
- class `MPS\Utils\Components\Queue\RabbitMQ\Console\QueueInitCommand` _extends QueueCommand_
  - `extendHandle(): void`

## src/Components/Queue/RabbitMQ/Consumer

- class `MPS\Utils\Components\Queue\RabbitMQ\Consumer\Job`
  - `handle(QueueJob $queueJob, array $payload): void`
- class `MPS\Utils\Components\Queue\RabbitMQ\Consumer\QueueJob` _extends Job_
  - `fire()`
  - `getHandler(): string`
  - `getHandlerMapItem(): array`
  - `payload(): array`
  - `putInFailedJob($e): void`
  - `getAttempts(): int`
  - `nack(): void`
  - `ack(): void`

## src/Components/Queue/RabbitMQ/DataObject

- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\DurabilityConfig` _extends DataObject_
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\ExchangeConfig` _extends DataObject implements DurableAwareInterface, AutoDeleteAwareInterface, ArgumentsAwareInterface_
  - `getName(): string`
  - `setName(string $name): self`
  - `getType(): ExchangeTypeEnum`
  - `setType(string|ExchangeTypeEnum $type): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\ExchangeDeleteConfig` _extends DataObject implements NoWaitAwareInterface, TicketAwareInterface_
  - `getName(): string`
  - `setName(string $name): self`
  - `getIfUnused(): bool`
  - `setIfUnused(bool $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\HandlerMapItemConfig` _extends DataObject implements RoutingKeyAwareInterface_
  - `getQueue(): string|QueueConfig`
  - `setQueue(string|array|QueueConfig $value): self`
  - `getExchange(): string|ExchangeConfig`
  - `setExchange(string|array|ExchangeConfig $value): self`
  - `getHandler(): string`
  - `setHandler(string $value): self`
  - `getDurability(): DurabilityConfig|array`
  - `setDurability(DurabilityConfig|array $value): self`
  - `isEnableFailedJobs(): bool`
  - `setEnableFailedJobs(bool $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\MessageConfig` _extends DataObject_
  - `isPersistent(): bool`
  - `setPersistent(bool $value): self`
  - `getApplicationHeaders(): array`
  - `setApplicationHeaders(array $values): self`
  - `pushApplicationHeaders(array ...$values): self`
  - `getPriority(): ?int`
  - `setPriority(?int $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\PutInConfig` _extends DataObject implements RoutingKeyAwareInterface_
  - `getPayload(): array|DataObjectInterface`
  - `setPayload(array|DataObjectInterface $value): self`
  - `getQueue(): ?QueueConfig`
  - `setQueue(?QueueConfig $value): self`
  - `getExchange(): ?ExchangeConfig`
  - `setExchange(?ExchangeConfig $value): self`
  - `getMessageConfig(): ?MessageConfig`
  - `setMessageConfig(?MessageConfig $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\QueueBindConfig` _extends DataObject implements RoutingKeyAwareInterface, QueueNameAwareInterface_
  - `getExchangeName(): string`
  - `setExchangeName(string $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\QueueConfig` _extends DataObject implements MaxPriorityAwareInterface, DurableAwareInterface, AutoDeleteAwareInterface, NoWaitAwareInterface, ArgumentsAwareInterface_
  - `__construct()`
  - `getName(): string`
  - `setName(string $value): self`
  - `isPassive(): bool`
  - `setPassive(bool $value): self`
  - `isExclusive(): bool`
  - `setExclusive(bool $value): self`
  - `getTicket(): ?int`
  - `setTicket(?int $value): self`
  - `setDeadLetter(int $messageTTL, string $exchange, string $routingKey = ''): self`
  - `setMaxPriority(int $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\QueueDeleteConfig` _extends DataObject implements QueueNameAwareInterface, NoWaitAwareInterface, TicketAwareInterface_
  - `getIfUnused(): bool`
  - `setIfUnused(bool $value): self`
  - `getIfEmpty(): bool`
  - `setIfEmpty(bool $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\QueuePurgeConfig` _extends DataObject implements QueueNameAwareInterface, NoWaitAwareInterface, TicketAwareInterface_
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\QueueUnbindConfig` _extends DataObject implements RoutingKeyAwareInterface, QueueNameAwareInterface_
  - `getExchangeName(): string`
  - `setExchangeName(string $value): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\RabbitMessage` _extends DataObject implements RoutingKeyAwareInterface_
  - `__construct(array|DataObject $payload = [], string $exchange = '', string $routingKey = '')`
  - `getPayload(): array|DataObject`
  - `setPayload(array|DataObject $payload): self`
  - `getExchange(): string`
  - `setExchange(string $exchange): self`
  - `getProperties(): RabbitMessageProperties`
  - `setProperties(RabbitMessageProperties $properties): self`
- class `MPS\Utils\Components\Queue\RabbitMQ\DataObject\RabbitMessageProperties` _extends DataObject_
  - `getDeliveryMode(): ?int`
  - `setDeliveryMode(?int $deliveryMode): self`
  - `toArray(StringCaseEnum $case = StringCaseEnum::DEFAULT, bool $recursive = false): array`

## src/Components/Queue/RabbitMQ/Enums

- enum `MPS\Utils\Components\Queue\RabbitMQ\Enums\ExchangeTypeEnum` _: string implements Enumerable_
  - кейсы: `DIRECT`, `FANOUT`, `TOPIC`, `HEADERS`

## src/Components/Queue/RabbitMQ/Facade

- class `MPS\Utils\Components\Queue\RabbitMQ\Facade\Rabbit` _extends Facade_

## src/Components/Queue/RabbitMQ/Factories

- class `MPS\Utils\Components\Queue\RabbitMQ\Factories\RabbitFactory`
  - `static makeConnectionByName(string $name = 'rabbitmq'): AMQPStreamConnection`
  - `static makeConnectionByParams(array $params = []): AMQPStreamConnection`
  - `static makeMessage(array|DataObjectInterface $payload, ?MessageConfig $config = null): AMQPMessage`
  - `static makeApplicationHeaders(array $values = []): AMQPTable`
  - `static makeQueueConfig(array|HandlerMapItemConfig $config, string $queueName): QueueConfig`

## src/Components/Queue/RabbitMQ/Helpers

- class `MPS\Utils\Components\Queue\RabbitMQ\Helpers\HandlerMapHelper`
  - `static get(string $connection, bool $reset = false): array`
  - `static extractQueue(array|HandlerMapItemConfig $config): string`
  - `static extractExchange(array|HandlerMapItemConfig $config, string $defaultType): array`
  - `static normalizeConfig(array|HandlerMapItemConfig $config): array`
- class `MPS\Utils\Components\Queue\RabbitMQ\Helpers\MessageHelper`
  - `static encode(object|array $value): string`
  - `static decode(string $value): mixed`

## src/Components/Queue/RabbitMQ/Interfaces

- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\ArgumentsAwareInterface`
  - `getArguments(): ?AMQPTable`
  - `setArguments(?AMQPTable $arguments): self`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\AutoDeleteAwareInterface`
  - `isAutoDelete(): bool`
  - `setAutoDelete(bool $value): self`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\ConnectionAwareInterface`
  - `getConnection(string $name = 'rabbitmq'): AMQPStreamConnection`
  - `setConnection(string $name = 'rabbitmq'): void`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\DurableAwareInterface`
  - `isDurable(): bool`
  - `setDurable(bool $value): self`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\MaxPriorityAwareInterface`
  - `setMaxPriority(int $value): self`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\NoWaitAwareInterface`
  - `isNoWait(): bool`
  - `setNoWait(bool $value): self`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\QueueNameAwareInterface`
  - `getQueueName(): string`
  - `setQueueName(string $value): self`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\RabbitManagerInterface` _extends ConnectionAwareInterface_
  - `putIn(array|DataObjectInterface $payload, string $queueName, string $exchange = '', bool $durable = true, bool $persistent = true): void`
  - `basicPublish(RabbitMessage $message): void`
  - `batchBasicPublish(RabbitMessage $message): void`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\RoutingKeyAwareInterface`
  - `getRoutingKey(): string`
  - `setRoutingKey(string $value): self`
- interface `MPS\Utils\Components\Queue\RabbitMQ\Interfaces\TicketAwareInterface`
  - `getTicket(): ?string`
  - `setTicket(?string $value): self`

## src/Components/Queue/RabbitMQ/Mocks

- class `MPS\Utils\Components\Queue\RabbitMQ\Mocks\RabbitManagerMock` _implements RabbitManagerInterface_
  - `putIn(array|DataObjectInterface $payload, string $queueName, string $exchange = '', bool $durable = true, bool $persistent = true): void`
  - `basicPublish(RabbitMessage $message): void`
  - `batchBasicPublish(RabbitMessage $message): void`

## src/Components/Queue/RabbitMQ/Traits

- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\ArgumentsAwareTrait`
  - `getArguments(): ?AMQPTable`
  - `setArguments(?AMQPTable $arguments): self`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\AutoDeleteAwareTrait`
  - `isAutoDelete(): bool`
  - `setAutoDelete(bool $value): self`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\ConnectionAwareTrait`
  - `getConnection(string $name = 'rabbitmq'): AMQPStreamConnection`
  - `setConnection(string $name = 'rabbitmq'): void`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\DurableAwareTrait`
  - `isDurable(): bool`
  - `setDurable(bool $value): self`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\MaxPriorityAwareTrait`
  - `setMaxPriority(int $value): self`
  - `getMaxPriority(): ?int`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\NoWaitAwareTrait`
  - `isNoWait(): bool`
  - `setNoWait(bool $value): self`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\QueueNameAwareTrait`
  - `getQueueName(): string`
  - `setQueueName(string $value): self`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\RoutingKeyAwareTrait`
  - `getRoutingKey(): string`
  - `setRoutingKey(string $value): self`
- trait `MPS\Utils\Components\Queue\RabbitMQ\Traits\TicketAwareTrait`
  - `getTicket(): ?string`
  - `setTicket(?string $value): self`

## src/Components/Queue/RabbitMQ/V2

- class `MPS\Utils\Components\Queue\RabbitMQ\V2\AlternateExchangeManager` _implements AlternateExchangeManagerInterface_
  - `__construct(protected QueueManagerInterface $queueManager)`
  - `init(ExchangeConfig $config): void`
  - `cleanup(string $exchangeName): void`
- class `MPS\Utils\Components\Queue\RabbitMQ\V2\DelayedQueueManager` _implements DelayedQueueManagerInterface_
  - `__construct(protected QueueManagerInterface $queueManager)`
  - `init(string $queueName, array|DurabilityConfig $config, string $connectionName = 'rabbitmq'): array`
  - `cleanup(string $queueName, array|DurabilityConfig $config, string $connectionName = 'rabbitmq'): array`
  - `handleJob(QueueJob $queueJob, DataObjectInterface|array $payload): bool`
  - `getTrace(): array`
- class `MPS\Utils\Components\Queue\RabbitMQ\V2\QueueManager` _implements QueueManagerInterface, LoggerAwareInterface_
  - `__construct(LoggerInterface $logger)`
  - `putInQueue(QueueConfig $queueConfig, array|DataObjectInterface $payload, string $exchangeName = '', ?MessageConfig $messageConfig = null): bool`
  - `putInExchange(string $exchangeName, array|DataObjectInterface $payload, string $routingKey = '', ?MessageConfig $messageConfig = null): bool`
  - `put(PutInConfig $config): bool`
  - `publish(RabbitMessage $message, ?AMQPChannel $channel = null): bool`
  - `batchPublish(RabbitMessage $message, ?AMQPChannel $channel = null): bool`
  - `declareQueue(QueueConfig $config, ?AMQPChannel $channel = null): bool`
  - `declareExchange(ExchangeConfig $config, ?AMQPChannel $channel = null): bool`
  - `bindQueue(QueueBindConfig $config, ?AMQPChannel $channel = null): bool`
  - `unbindQueue(QueueUnbindConfig $config, ?AMQPChannel $channel = null): bool`
  - `purgeQueue(QueuePurgeConfig $config, ?AMQPChannel $channel = null): bool`
  - `deleteQueue(QueueDeleteConfig $config, ?AMQPChannel $channel = null): bool`
  - `deleteExchange(ExchangeDeleteConfig $config, ?AMQPChannel $channel = null): bool`

## src/Components/Queue/RabbitMQ/V2/DataObjects

- class `MPS\Utils\Components\Queue\RabbitMQ\V2\DataObjects\RabbitMessage` _extends DataObject implements RoutingKeyAwareInterface_
  - `__construct(array|DataObjectInterface $payload = [], string $exchange = '', string $routingKey = '')`
  - `getPayload(): array|DataObject`
  - `setPayload(array|DataObject $payload): self`
  - `getExchange(): string`
  - `setExchange(string $exchange): self`
  - `getConfig(): ?MessageConfig`
  - `setConfig(MessageConfig $value): self`

## src/Components/Queue/RabbitMQ/V2/Interfaces

- interface `MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\AlternateExchangeManagerInterface`
  - константы: `EXCHANGE_SUFFIX`, `QUEUE_SUFFIX`
  - `init(ExchangeConfig $config): void`
  - `cleanup(string $exchangeName): void`
- interface `MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\DelayedQueueManagerInterface`
  - `init(string $queueName, array|DurabilityConfig $config, string $connectionName = 'rabbitmq'): array`
  - `cleanup(string $queueName, array|DurabilityConfig $config, string $connectionName = 'rabbitmq'): array`
  - `handleJob(QueueJob $queueJob, DataObjectInterface|array $payload): bool`
- interface `MPS\Utils\Components\Queue\RabbitMQ\V2\Interfaces\QueueManagerInterface` _extends ConnectionAwareInterface_
  - `putInQueue(QueueConfig $queueConfig, array|DataObjectInterface $payload, string $exchangeName = '', ?MessageConfig $messageConfig = null): bool`
  - `putInExchange(string $exchangeName, array|DataObjectInterface $payload, string $routingKey = '', ?MessageConfig $messageConfig = null): bool`
  - `put(PutInConfig $config): bool`
  - `publish(RabbitMessage $message, ?AMQPChannel $channel = null): bool`
  - `batchPublish(RabbitMessage $message, ?AMQPChannel $channel = null): bool`
  - `declareQueue(QueueConfig $config, ?AMQPChannel $channel = null): bool`
  - `declareExchange(ExchangeConfig $config, ?AMQPChannel $channel = null): bool`
  - `bindQueue(QueueBindConfig $config, ?AMQPChannel $channel = null): bool`
  - `unbindQueue(QueueUnbindConfig $config, ?AMQPChannel $channel = null): bool`
  - `purgeQueue(QueuePurgeConfig $config, ?AMQPChannel $channel = null): bool`
  - `deleteQueue(QueueDeleteConfig $config, ?AMQPChannel $channel = null): bool`
  - `deleteExchange(ExchangeDeleteConfig $config, ?AMQPChannel $channel = null): bool`

## src/Components/Queue/RabbitMQ/V2/Mocks

- class `MPS\Utils\Components\Queue\RabbitMQ\V2\Mocks\AlternateExchangeManagerMock` _implements AlternateExchangeManagerInterface_
  - `init(ExchangeConfig $config): void`
  - `cleanup(string $exchangeName): void`
- class `MPS\Utils\Components\Queue\RabbitMQ\V2\Mocks\DelayedQueueManagerMock` _implements DelayedQueueManagerInterface_
  - `__construct(protected QueueManagerInterface $queueManager)`
  - `init(string $queueName, array|DurabilityConfig $config, string $connectionName = 'rabbitmq'): array`
  - `cleanup(string $queueName, array|DurabilityConfig $config, string $connectionName = 'rabbitmq'): array`
  - `handleJob(QueueJob $queueJob, DataObjectInterface|array $payload): bool`
  - `getTrace(): array`
- class `MPS\Utils\Components\Queue\RabbitMQ\V2\Mocks\QueueManagerMock` _implements QueueManagerInterface_
  - `putInQueue(QueueConfig $queueConfig, array|DataObjectInterface $payload, string $exchangeName = '', ?MessageConfig $messageConfig = null): bool`
  - `putInExchange(string $exchangeName, array|DataObjectInterface $payload, string $routingKey = '', ?MessageConfig $messageConfig = null): bool`
  - `put(PutInConfig $config): bool`
  - `publish(RabbitMessage $message, ?AMQPChannel $channel = null): bool`
  - `batchPublish(RabbitMessage $message, ?AMQPChannel $channel = null): bool`
  - `declareQueue(QueueConfig $config, ?AMQPChannel $channel = null): bool`
  - `declareExchange(ExchangeConfig $config, ?AMQPChannel $channel = null): bool`
  - `bindQueue(QueueBindConfig $config, ?AMQPChannel $channel = null): bool`
  - `unbindQueue(QueueUnbindConfig $config, ?AMQPChannel $channel = null): bool`
  - `purgeQueue(QueuePurgeConfig $config, ?AMQPChannel $channel = null): bool`
  - `deleteQueue(QueueDeleteConfig $config, ?AMQPChannel $channel = null): bool`
  - `deleteExchange(ExchangeDeleteConfig $config, ?AMQPChannel $channel = null): bool`

## src/Components/Redis/Helpers

- class `MPS\Utils\Components\Redis\Helpers\KeyHelper`
  - `static generate(string ...$values): string`

## src/Components/Redis/Interfaces/Structures

- interface `MPS\Utils\Components\Redis\Interfaces\Structures\SetInterface` _extends StructureInterface_
  - `get(string $key): array`
  - `add(string $key, string|int|float ...$values): int`
  - `bulk(string $key, string|int|float ...$values): bool`
  - `has(string $key, string|int|float $value): bool`
  - `count(string $key): int`
  - `delete(string $key, string|int|float $value): int`
- interface `MPS\Utils\Components\Redis\Interfaces\Structures\SortedSetInterface` _extends StructureInterface_
  - `get(string $key, int $start = 0, int $stop = -1, bool $withScores = false): array`
  - `add(string $key, string|int|float $value, int $score = 0): int`
  - `has(string $key, string|int|float $value): bool`
  - `count(string $key, int|string $min = '-inf', int|string $max = '+inf'): int`
  - `delete(string $key, string|int|float $value): int`
- interface `MPS\Utils\Components\Redis\Interfaces\Structures\StructureInterface`

## src/Components/Redis/Interfaces/Tag

- interface `MPS\Utils\Components\Redis\Interfaces\Tag\TagManagerInterface`
  - `get(): array`
  - `add(string $value): self`
  - `bulk(array $values): self`
  - `remove(string $value): int`
  - `clear(): void`
- interface `MPS\Utils\Components\Redis\Interfaces\Tag\TagStorageInterface`
  - `get(string $tag): array`
  - `add(string $tag, string $value): int`
  - `bulk(string $tag, array $values): bool`
  - `remove(string $tag, string $value): int`

## src/Components/Redis/Structures

- class `MPS\Utils\Components\Redis\Structures\Set` _extends Structure implements SetInterface_ — Класс для взаимодействия с типом данных множества
  - `add(string $key, string|int|float ...$values): int`
  - `bulk(string $key, string|int|float ...$values): bool`
  - `get(string $key): array`
  - `has(string $key, string|int|float $value): bool`
  - `count(string $key): int`
  - `delete(string $key, string|int|float $value): int`
- class `MPS\Utils\Components\Redis\Structures\SortedSet` _extends Structure implements SortedSetInterface_ — Класс для взаимодействия с типом данных упорядоченные множества
  - `add(string $key, string|int|float $value, int|float $score = 0): int`
  - `bulk(string $key, string|int|float ...$values): bool`
  - `get(string $key, int $start = 0, int $stop = -1, bool $withScores = false): array`
  - `has(string $key, string|int|float $value): bool`
  - `count(string $key, int|string $min = '-inf', int|string $max = '+inf'): int`
  - `delete(string $key, string|int|float $value): int`
- class `MPS\Utils\Components\Redis\Structures\Structure` _implements StructureInterface_
  - `__construct(protected RedisManager $manager, protected string $connectionName = 'cache')`
  - `withConnection(string $name): static`
  - `pipeline(callable $callback): mixed`
  - `__call(string $command, array $arguments): mixed`

## src/Components/Redis/Tag

- class `MPS\Utils\Components\Redis\Tag\TagFactory`
  - `__construct(private readonly SetInterface $set, private readonly SortedSetInterface $sortedSet)`
  - `makeManager(string $name, bool $sorted = false): TagManagerInterface`
- class `MPS\Utils\Components\Redis\Tag\TagManager` _implements TagManagerInterface_
  - `__construct(private string $name, private TagStorageInterface $storage)`
  - `get(): array`
  - `add(string $value): self`
  - `bulk(array $values): self`
  - `remove(string $value): int`
  - `clear(): void`

## src/Components/Redis/Tag/Storages

- class `MPS\Utils\Components\Redis\Tag\Storages\SetTagStorage` _implements TagStorageInterface_
  - `__construct(private SetInterface $structure)`
  - `get(string $tag): array`
  - `add(string $tag, string $value): int`
  - `bulk(string $tag, array $values): bool`
  - `remove(string $tag, string $value): int`
  - `delete(string $tag): void`
- class `MPS\Utils\Components\Redis\Tag\Storages\SortedSetTagStorage` _implements TagStorageInterface_
  - `__construct(private SortedSetInterface $structure)`
  - `get(string $tag): array`
  - `bulk(string $tag, array $values): bool`
  - `add(string $tag, string $value): int`
  - `remove(string $tag, string $value): int`
  - `delete(string $tag): void`

## src/Components/Slug/Services

- interface `MPS\Utils\Components\Slug\Services\SlugServiceInterface`
  - `setDataSlug(CommandDataObject $dto): void`
  - `generateSlug(string $value): string`
  - `getBySlug(string $value): ?Model`
  - `getByMultiSlug(array $values): Collection`
  - `checkSlugUnique(string $value, ?int $entityId = null): void`
- trait `MPS\Utils\Components\Slug\Services\SlugServiceTrait`
  - `setDataSlug(CommandDataObject $dto): void`
  - `generateSlug(string $value): string`
  - `getBySlug(string $value): ?Model`
  - `getByMultiSlug(array $values): Collection`
  - `checkSlugUnique(string $value, ?int $entityId = null): void`

## src/Components/StatusChange/Actions

- class `MPS\Utils\Components\StatusChange\Actions\StatusChangeAction` _extends Action_
  - `run(int $id): JsonResponse`

## src/Components/StatusChange/Exceptions

- class `MPS\Utils\Components\StatusChange\Exceptions\EntityAlreadyThisStatusException` _extends ValidationAppException_
  - `__construct(?string $message = null, array $details = [], bool $isTrackable = true)`
- class `MPS\Utils\Components\StatusChange\Exceptions\StatusNotDefinedException` _extends ValidationAppException_
  - `__construct(?string $message = null, array $details = [], bool $isTrackable = true)`

## src/Components/StatusChange/Requests

- class `MPS\Utils\Components\StatusChange\Requests\StatusChangeRequest` _extends CommandRequest_
  - `rules(): array`
  - `attributes(): array`

## src/Components/StatusChange/Services

- interface `MPS\Utils\Components\StatusChange\Services\StatusChangeServiceInterface` _extends CRUDServiceInterface_
  - `statusChange(string|int $id, array $data): bool`
  - `statusChangeByModel(Model $model, array $data): bool`
- trait `MPS\Utils\Components\StatusChange\Services\StatusChangeServiceTrait`
  - `statusChange(string|int $id, array $data): bool`
  - `statusChangeByModel(Model $model, array $data): bool`

## src/Components/StressTest

- class `MPS\Utils\Components\StressTest\StressTestContext`
  - `enable(array $metadata = []): void`
  - `disable(): void`
  - `isEnabled(): bool`
  - `metadata(): array`

## src/Components/StressTest/Console

- class `MPS\Utils\Components\StressTest\Console\GenerateStressTestTokenCommand` _extends Command_
  - `__construct(protected readonly JwtManager $jwt)`
  - `handle(): int`

## src/Components/StressTest/Enums

- enum `MPS\Utils\Components\StressTest\Enums\StressTestHeaderEnum` _: string implements Enumerable_
  - кейсы: `ENABLED`, `TOKEN`
  - `static labels(): array`

## src/Components/StressTest/Helpers

- class `MPS\Utils\Components\StressTest\Helpers\StressTestHelper`
  - `static secret(): ?string`
  - `static isThrottleDisabled(): bool`
  - `static resolveIp(Request $request): string` — Resolves the real client IP inside a k8s cluster by checking proxy headers X-Original-Forwarded-For and X-Real-IP before falling back to $request->ip().
  - `static isAllowedIp(string $ip): bool`

## src/Components/StressTest/Http/Middleware

- class `MPS\Utils\Components\StressTest\Http\Middleware\StressTestThrottleRequests` _extends ThrottleRequests_
  - `__construct(protected readonly StressTestContext $context, RateLimiter $limiter)`
  - `handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = ''): mixed`
- class `MPS\Utils\Components\StressTest\Http\Middleware\StressTestingByFlagMiddleware`
  - `__construct(protected readonly StressTestContext $context)`
  - `handle(Request $request, Closure $next): mixed`
- class `MPS\Utils\Components\StressTest\Http\Middleware\StressTestingByTokenMiddleware`
  - `__construct(protected readonly StressTestContext $context, protected readonly JwtManager $jwtManager)`
  - `handle(Request $request, Closure $next): mixed`

## src/Components/Tree

- class `MPS\Utils\Components\Tree\ServiceProvider` _extends ModuleServiceProvider_
  - `register(): void`
- class `MPS\Utils\Components\Tree\TreeHelper`
  - `static build(array $flatTree, null|int|string $parentId = null, string $nodeIdAttribute = 'nodeId', string $parentNodeIdAttribute = 'parentNodeId', ?\Closure $callback = null): array`

## src/Components/Tree/DataObjects

- class `MPS\Utils\Components\Tree\DataObjects\TreeCommandDataObject` _extends DataObject_
  - `getAction(): TreeCommandEnum`
  - `setAction(TreeCommandEnum $action): self`
  - `getData(): array`
  - `getInsertData(): array`
  - `setData(DataObjectInterface|array $data): self`
  - `setInsertData(DataObjectInterface|array $insertData): self`
  - `isSuccess(): bool`
  - `setSuccess(bool $success): self`
  - `processDataCallback(Closure $function): void`

## src/Components/Tree/Enums

- enum `MPS\Utils\Components\Tree\Enums\TreeCommandEnum` _: string implements Enumerable_
  - кейсы: `CREATE_NODE`, `UPSERT_TREE`, `MOVE_NODE`, `DELETE_NODE`
  - `static labels(): array`

## src/Components/Tree/Repositories

- interface `MPS\Utils\Components\Tree\Repositories\TreeRepositoryInterface` _extends CRUDRepositoryInterface_
  - `getById(string $id): ?StdClass`
  - `getByIds(array $ids): Collection`
  - `getByEntityId(int $id): Collection`
  - `getByEntityIds(array $ids): Collection`
  - `getUniqueNode(int $entityId, ?string $parentId = null): ?StdClass`
  - `getDescendantsByPath(string $path): Collection`
  - `getDescendants(int $entityId, bool $withSelf = false): Collection`
  - `getDescendantsById(string $id, bool $withSelf = false): Collection`
- trait `MPS\Utils\Components\Tree\Repositories\TreeRepositoryTrait`
  - `getById(string $id): ?StdClass`
  - `getByIds(array $ids): Collection`
  - `getByEntityId(int $id): Collection`
  - `getByEntityIds(array $ids): Collection`
  - `getUniqueNode(int $entityId, string $parentId = null): ?StdClass`
  - `getDescendants(int $entityId, bool $withSelf = false): Collection`
  - `getDescendantsById(string $id, bool $withSelf = false): Collection`
  - `getDescendantsByPath(string $path): Collection`

## src/Components/Tree/Services

- class `MPS\Utils\Components\Tree\Services\TreeCacheService` _implements TreeCacheServiceInterface_
  - `setKey(string $key): self`
  - `getOrSet(Closure $callback, ?int $ttl = null): array`
  - `set(Closure $callback, ?int $ttl = null): bool`
- interface `MPS\Utils\Components\Tree\Services\TreeCacheServiceAwareInterface`
  - `getTreeCacheService(): ?TreeCacheServiceInterface`
  - `setTreeCacheService(TreeCacheServiceInterface $treeCacheService): void`
- trait `MPS\Utils\Components\Tree\Services\TreeCacheServiceAwareTrait`
  - `getTreeCacheService(): ?TreeCacheServiceInterface`
  - `setTreeCacheService(TreeCacheServiceInterface $treeCacheService): void`
- interface `MPS\Utils\Components\Tree\Services\TreeCacheServiceInterface` _extends CacheManagerAwareInterface_
  - `setKey(string $key): self`
  - `getOrSet(Closure $callback, ?int $ttl = null): array`
  - `set(Closure $callback, ?int $ttl = null): bool`
- interface `MPS\Utils\Components\Tree\Services\TreeServiceInterface`
  - `treeRepository(): TreeRepositoryInterface`
  - `buildTree(array $flatTree, null|int|string $parentId = null, ?Closure $callback = null, string $nodeIdAttribute = 'id', string $parentNodeIdAttribute = 'parent_id'): array`
  - `ancestorsOf(int $entityId): array`
  - `ancestorsOfMany(array $entityIds): array`
  - `getAncestorsByNodeId(string $id): array`
  - `getAncestorsByNodeIds(array $ids): array`
  - `extractAncestorsFromCollection(Collection $treeCollection): array`
  - `descendantsOf(int $entityId, bool $withSelf = false): array`
  - `getDescendantsByNodeId(string $id, bool $withSelf = false): array`
  - `createNode(int $entityId, ?string $nodeId = null): array`
  - `upsertTree(CommandDataObject $dto): bool`
  - `moveNode(string $nodeId, ?string $toNodeId = null): bool`
  - `deleteNode(string $nodeId): bool`
  - `setShouldCacheTree(bool $shouldCacheTree): self`
  - `getTreeData(bool $cache = false): array`
  - `refreshTreeDataCache(): bool`
- trait `MPS\Utils\Components\Tree\Services\TreeServiceTrait`
  - `buildTree(array $flatTree, null|int|string $parentId = null, Closure $callback = null, string $nodeIdAttribute = 'id', string $parentNodeIdAttribute = 'parent_id'): array`
  - `ancestorsOf(int $entityId): array`
  - `ancestorsOfMany(array $entityIds): array`
  - `getAncestorsByNodeId(string $nodeId): array`
  - `getAncestorsByNodeIds(array $nodeIds): array`
  - `extractAncestorsFromCollection(Collection $treeCollection): array`
  - `descendantsOf(int $entityId, bool $withSelf = false): array`
  - `getDescendantsByNodeId(string $nodeId, bool $withSelf = false): array`
  - `createNode(int $entityId, ?string $nodeId = null): array`
  - `upsertTree(CommandDataObject $dto): bool`
  - `moveNode(string $nodeId, string $toNodeId = null): bool`
  - `deleteNode(string $nodeId): bool`
  - `setShouldCacheTree(bool $shouldCacheTree): self`
  - `getTreeData(bool $cache = false): array`
  - `refreshTreeDataCache(): bool`

## src/Components/Uuid/Services

- interface `MPS\Utils\Components\Uuid\Services\UuidServiceInterface`
  - `getByUuid(string $value): ?Model`
  - `getByUuidOrFail(string $value, ?string $message = null): Model`
  - `getByUuids(array $values): Collection`
- trait `MPS\Utils\Components\Uuid\Services\UuidServiceTrait`
  - `getByUuid(string $value): ?Model`
  - `getByUuidOrFail(string $value, ?string $message = null): Model`
  - `getByUuids(array $values): Collection`

## src/Enums

- enum `MPS\Utils\Enums\AppEnvEnum` _: string implements Enumerable_
  - кейсы: `LOCAL`, `STAGE`, `TEST`, `PROD`, `PRE_PROD`
  - `static fromAppConfig(string $key = 'app.env'): ?self`
  - `is(self $value): bool`
- class `MPS\Utils\Enums\LanguageStringEnum` _extends CoreLanguageStringEnum_ — Перечисление языков
  - `static exclude(): array`
  - `static labels(): array`
- class `MPS\Utils\Enums\RegexEnum` _extends Enum_
  - константы: `COORDINATE`, `COORDINATES`, `MOBILE_PHONE`, `EMAIL`, `USERNAME`, `SLUG`, `MOBILE_PHONE_OR_EMAIL`

## src/Helpers

- class `MPS\Utils\Helpers\AppEnvHelper`
  - `static is(AppEnvEnum $enum): bool`
- class `MPS\Utils\Helpers\ArchiveHelper`
  - `static isArchive(string $filePath): bool`
- class `MPS\Utils\Helpers\ArrayHelper`
  - `static removeNullValues(array $data): array` — Рекурсивно удаляет null значения.
- class `MPS\Utils\Helpers\Base64Helper`
  - `static encode(string $data): string`
  - `static decode(string $data): string`
- class `MPS\Utils\Helpers\CSVHelper`
  - `static readByPath(string $filePath, \Closure $callback, string $delimiter = ';', int $maxLineSize = 65536): void`
  - `static readResource($fp, string $length, string $delimiter = ',', string $enclosure = '"'): mixed` — Gets line from file pointer and parses for CSV fields.
- class `MPS\Utils\Helpers\ConsoleHelper`
  - `static getOutput(): OutputStyle`
  - `static makeOutput(string $inputArgs = ''): OutputStyle`
- class `MPS\Utils\Helpers\HmacHelper`
  - `static compute(string $data, string $secret, string $algorithm = 'sha256'): string`
  - `static verify(string $computed, string $expected): bool`
- class `MPS\Utils\Helpers\LanguageHelper`
  - `static getNumericByString(string $value): ?int`
  - `static getStringByNumeric(int $value): ?string`
- class `MPS\Utils\Helpers\MarkdownHelper`
  - `static escapeSpecialChars(string $value): string`
- class `MPS\Utils\Helpers\NumberHelper`
  - `static asMoney(float $number): string`
- class `MPS\Utils\Helpers\ProcessHelper`
  - `static measure(callable $operation, string $tag, array $groups = []): mixed`
  - `static logMemoryUsage(string $tag, array $groups = []): void`
  - `static logMemoryPeakUsage(string $tag, array $groups = []): void`
- class `MPS\Utils\Helpers\StringHelper`
  - `static clear(string $value): string`
  - `static cleanMultiSpaces(string $value): string`

## src/Http/Middleware

- class `MPS\Utils\Http\Middleware\ApiResponseMiddleware`
  - `__construct(LogStorageInterface $logStorage)`
  - `handle(Request $request, Closure $next): JsonResponse|Response`
- class `MPS\Utils\Http\Middleware\LocaleMiddleware`
  - `handle(Request $request, \Closure $next): mixed`

## src/Interfaces

- interface `MPS\Utils\Interfaces\Contextable`
  - `getContext(): array`
  - `withContext(array $context = [])`
  - `withoutContext(?array $keys = null)`
  - `stashContext(int $index = 0)`
  - `recoveryContextFromStorage($index = 0): bool`
- interface `MPS\Utils\Interfaces\NameAwareInterface`
  - `getName(): string`
  - `setName(string $value): self`
- interface `MPS\Utils\Interfaces\SourceDataAwareInterface`
  - `getSourceData(): array`
  - `setSourceData(array $values): self`

## src/Providers

- class `MPS\Utils\Providers\EventServiceProvider` _extends LaravelEventServiceProvider_
  - `__construct($dispatcher = null)`
  - `handleBeforeModify(BeforeModifyEvent $event): void`
  - `handleAfterSearch(AfterSearchEvent $event): void`
  - `handleAfterModify(AfterModifyEvent $event): void`

## src/Traits

- trait `MPS\Utils\Traits\ContextableTrait`
  - `getContext(): array`
  - `withContext(array $context = [])` — Add context to all future logs.
  - `withoutContext(?array $keys = null)` — Flush the log context on all currently resolved channels.
  - `stashContext(int $index = 0)`
  - `recoveryContextFromStorage($index = 0): bool`
- trait `MPS\Utils\Traits\NameAwareTrait`
  - `getName(): string`
  - `setName(string $value): self`
- trait `MPS\Utils\Traits\SourceDataAwareTrait`
  - `getSourceData(): array`
  - `setSourceData(array $values): self`
- trait `MPS\Utils\Traits\UserIdAwareTrait`
  - `getUserId(): ?int`
  - `setUserId(?int $user): self`

## src/Validation

- class `MPS\Utils\Validation\MultipleRegexRule` _implements ValidationRule_
  - `getRegexes(): array`
  - `validate(string $attribute, mixed $value, \Closure $fail): void`
- class `MPS\Utils\Validation\RegexRule` _implements ValidationRule_
  - `getRegex(): string`
  - `validate(string $attribute, mixed $value, \Closure $fail): void`

