# mps/utils

Набор готовых инфраструктурных компонентов поверх `mps/core`.
Если `core` — каркас и абстракции, то `utils` — конкретные реализации для реальных задач.

Требует PHP 8.3–8.5, Laravel 11–12.

## Установка

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://git.mpinnovations.kz/mps/packages/utils.git"
    }
]
```

```bash
composer require mps/utils
php artisan vendor:publish --provider="MPS\Utils\ServiceProvider"
```

## Код стайл фиксер
```bash
composer csfix-validate
composer csfix
```

## Статический анализатор кода
```bash
composer phpstan
```

## Структура пакета

```
src/
├── Components/
│   ├── Authenticate/     — Basic Auth middleware
│   ├── Cache/            — CacheManager (+ SmartCache в новом релизе)
│   ├── Database/         — вспомогательные DB-утилиты
│   ├── Documentation/    — Swagger scanner
│   ├── ElasticSearch/    — CRUD поверх ElasticSearch
│   ├── Error/            — MessageBag
│   ├── Excel/            — чтение XLS/XLSX
│   ├── ExcelWriter/      — запись XLSX/ODS
│   ├── Exception/        — ExceptionContextFactory
│   ├── FileSystem/       — FileManager + UploadManager
│   ├── HealthCheck/      — Liveness/Readiness эндпоинты
│   ├── Http/             — HttpClient + ConcurrentRequestManager
│   ├── Integration/      — ServiceBus
│   ├── ItemsContainer/   — deprecated → mps/core
│   ├── Jwt/              — генерация и валидация JWT (HS256)
│   ├── Logger/           — логгер с чанкованием и LogContext
│   ├── MultiLanguage/    — переводы сущностей (i18n)
│   ├── Prometheus/       — метрики HTTP-запросов
│   ├── QueryFilters/     — фильтры запросов V1 (deprecated) + V2
│   ├── Queue/            — RabbitMQ V1 (deprecated) + V2
│   ├── Slug/             — генерация и валидация слагов
│   ├── StatusChange/     — смена статуса с валидацией
│   ├── StressTest/       — маркировка нагрузочных запросов
│   ├── Tree/             — иерархические структуры
│   └── Uuid/             — поиск по UUID
├── Helpers/
│   ├── AppEnvHelper.php    — определение окружения (local/testing/production)
│   ├── ArchiveHelper.php   — работа с ZIP-архивами
│   ├── ArrayHelper.php     — утилиты для массивов
│   ├── Base64Helper.php    — URL-safe Base64 encode/decode
│   ├── ConsoleHelper.php   — вывод в консоль
│   ├── CSVHelper.php       — чтение и запись CSV
│   ├── HmacHelper.php      — HMAC-подпись (hash_hmac)
│   ├── LanguageHelper.php  — работа с локалями
│   ├── MarkdownHelper.php  — парсинг Markdown
│   ├── NumberHelper.php    — форматирование чисел
│   ├── ProcessHelper.php   — запуск shell-процессов
│   └── StringHelper.php    — утилиты для строк
├── Traits/
│   ├── ContextableTrait.php
│   ├── NameAwareTrait.php
│   ├── SourceDataAwareTrait.php
│   └── UserIdAwareTrait.php
├── Http/Middleware/      — общие HTTP middleware
├── Validation/           — MultipleRegexRule, RegexRule
└── ServiceProvider.php
```

---

## Компоненты

| Компонент | Файл | Описание |
|---|---|---|
| HttpClient | [http-client.md](docs/http-client.md) | HTTP-клиент + concurrent requests |
| Logger | [logger.md](docs/logger.md) | Логгер с чанкованием и LogContext |
| RabbitMQ | [rabbitmq.md](docs/rabbitmq.md) | Очереди V1 (deprecated) + V2 |
| HealthCheck | [health-check.md](docs/health-check.md) | Liveness/Readiness эндпоинты |
| SlowQueryDetector | [slow-query-detector.md](docs/slow-query-detector.md) | Детектор медленных SQL-запросов |
| QueryFilters | [query-filters.md](docs/query-filters.md) | Фильтры запросов V2 |
| ServiceBus | [service-bus.md](docs/service-bus.md) | HTTP-репозиторий для internal API |
| FileSystem | [file-system.md](docs/file-system.md) | FileManager + UploadManager |
| Cache | [cache.md](docs/cache.md) | CacheManager (+ SmartCache в новом релизе) |
| StatusChange | [status-change.md](docs/status-change.md) | Смена статуса с валидацией |
| MultiLanguage | [multi-language.md](docs/multi-language.md) | Переводы сущностей (i18n) |
| Uuid | [uuid.md](docs/uuid.md) | Поиск сущностей по UUID |
| Slug | [slug.md](docs/slug.md) | Генерация и валидация слагов |
| ElasticSearch | [elasticsearch.md](docs/elasticsearch.md) | CRUD поверх ElasticSearch |
| Tree | [tree.md](docs/tree.md) | Иерархические структуры |
| Prometheus | [prometheus.md](docs/prometheus.md) | Метрики HTTP-запросов |
| Exception | [exception.md](docs/exception.md) | ExceptionContextFactory |
| Excel | [excel.md](docs/excel.md) | Чтение XLS/XLSX |
| ExcelWriter | [excel-writer.md](docs/excel-writer.md) | Запись Excel/ODS |
| Authenticate | [authenticate.md](docs/authenticate.md) | Basic Auth middleware |
| MessageBag | [message-bag.md](docs/message-bag.md) | Коллектор ошибок |
| ItemsContainer | [items-container.md](docs/items-container.md) | deprecated → использовать mps/core |
| Documentation | [documentation.md](docs/documentation.md) | Swagger scanner |
| Jwt | [jwt.md](docs/jwt.md) | Генерация и валидация JWT (HS256) |
| StressTest | [stress-test.md](docs/stress-test.md) | Маркировка нагрузочных запросов, обход throttle |

---

## Известные проблемы

| Приоритет | Проблема | Файл |
|---|---|---|
| Высокий | `static $isApply` в HttpClient — сломает конфиг при Octane | `Http/HttpClient.php` |
| Высокий | `LogContext` — статика, контекст утекает между запросами при Octane | `Logger/LogContext.php` |
| Средний | `TreeCacheService` использует deprecated `getOrCreate` | `Tree/Services/TreeCacheService.php` |
| Средний | Опечатка `CacheMangerAwareTait` | `Cache/CacheMangerAwareTait.php` |
| Низкий | `ConcurrentRequestManager` — проверить биндинг (bind vs singleton) | `Http/ConcurrentRequestManager.php` |
| Низкий | `ServiceBus\Repository` — retry/timeout захардкожены | `Integration/ServiceBus/Repositories/Repository.php` |

---

## Правила использования

- **RabbitMQ** — только V2 (`QueueManagerInterface`), V1 deprecated
- **QueryFilters** — только V2 (`MPS\Utils\Components\QueryFilters\V2\*`)
- **FileSystem** — `UploadManager` для загрузки, `FileManager` только для get/has/put/getUrl
- **LogContext** — вызывать `LogContext::clear()` в middleware при Octane

