# Граф правил — сводка

Узлов 1138, связей 4325, групп 11. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- agents/df-analyst.md
- agents/df-architect.md
- agents/df-php.md
- agents/df-qa.md
- agents/df-review.md
- agents/df-security.md
- skills/dev-mr/SKILL.md
- stacks/go/go-sdk/entrypoint.md
- stacks/go/go-sdk/infra/redis.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- Транзакции — 23
- Слои — 22
- Репозиторий — 22
- Возможности SDK — 21
- Лог отклонений rules-log — 20
- Внешние интеграции — 18
- Подтверждение коммита и пуша — 18
- Субагенты — 17
- Безопасность — 17
- Конкурентность — 17
- Unit-тесты — 17
- Анализ связей — 17
- DI — 17
- Swagger — 17
- RabbitMQ — 16
- Workflow — 16
- миграции БД — 16
- Авторизация и права — 16
- хелперы и переиспользование — 16
- именование — 16

## Кандидаты в противоречия (conflicts_with)
C1. [agents/df-review.md § п.2] ««Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)» VS [approaches/process/planning.md § Волны] «Ревью ⇄ исправления — до 3 кругов» (0.15)
C2. [approaches/patterns/performance.md § База данных] «Каждый запрос — с таймаутом (Go context.WithTimeout, 10s по умолчанию; PHP — в конфиге)» VS [stacks/go/go-sdk/infra/http.md § Конфигурация; repository.go] «Таймаут builder по умолчанию 30s; таймаут — SetRequestTimeout на каждый запрос, адрес и таймаут — из конструктора» (0.15)
C3. [stacks/go/checklist.md § п. 39] «п.39 Прогнаны go build/vet/test ./... и gofmt -l internal pkg (с горутинами -race); не запускал — так и написано» VS [stacks/go/examples/tariff/README.md § вступление] «Проверено 2026-10-20 против gosdk-core v1.0.9, rabbit-core v1.0.10, redis-core v1.0.7 и др. (Go 1.26+): go build/vet/test -race, gofmt -l .» (0.2)
C4. [stacks/go/go-sdk/entrypoint/rabbit.md § «Правила»] «Работа с RabbitMQ только через gosdk-rabbit-core (пакет github.com/exgamer/gosdk-rabbit-core)» VS [stacks/go/examples/tariff/README.md § вступление] «Проверено 2026-10-20 против gosdk-core v1.0.9, rabbit-core v1.0.10, redis-core v1.0.7 и др. (Go 1.26+): go build/vet/test -race, gofmt -l .» (0.25)
C5. [stacks/go/go-sdk/entrypoint/rabbit.md § «Правила»] «Путь consumer обязан содержать internal/entrypoints/{context}/rabbit/; иначе [ОШИБКА] (в т.ч. entrypoint/consumer/ без rabbit/)» VS [stacks/go/structure.md § «Entrypoint Layer»] «Каталог internal в контексте нельзя; consumer — контекст consumer, вебхуки — integration, служебные — system/http (G-4)» (0.15)
C6. [stacks/go/go-sdk/entrypoint/rabbit.md § «Правила»] «Ошибка десериализации payload — постоянная: вернуть nil (ACK), не error» VS [stacks/go/examples/tariff/README.md § tariff_consumer.go] «Consumer: битый payload — CaptureError и ACK; временная — NACK в retry, после N попыток (x-death) — CaptureError и ACK; контекст consumer, не internal» (0.2)
C7. [stacks/go/go-sdk/workflow.md § Правила] «Инфраструктура только через интерфейсы; сервисы доменов можно конкретным типом, интерфейс «на всякий случай» не заводится» VS [stacks/php/conventions.md § п.5] «RepositoryInterface и ServiceInterface — всегда; Command/Query — без интерфейса» (0.25)
C8. [stacks/php/conventions.md § п.2a] «Форма (1): CRUDRepository::search(..., int $perPage = 30, ...)» VS [stacks/php/mps-core/repository.md § Форма (1)] «Форма (1): контроллер setPerPage(validated(per_page, 20))» (0.2)
C9. [stacks/php/mps-core/migrations.md § «ALTER TABLE с заполнением»] «Data-миграция пишет через типизированный метод репозитория (один UPDATE … WHERE, P-20); зависимость — app(Interface::class)» VS [architecture/layers.md § «DI — только в сборке»] «DI только в сборке; резолв из контейнера (di.Resolve, app(), resolve()) в бизнес-коде — [ОШИБКА]; Go — сервисы конкретным типом, PHP — ServiceInterface всегда» (0.25)
C10. [stacks/php/mps-core/migrations.md § «ALTER TABLE с заполнением»] «Новая обязательная колонка в таблице с данными — три миграции: nullable → данные → снять nullable» VS [stacks/go/examples/tariff/README.md § migrations/] «Миграция уникального индекса: до индекса проверка дублей, нашлись — падает со списком; данные не правятся молча; FK с RESTRICT» (0.15)
C11. [stacks/php/mps-core/repository.md § Форма (1)] «Форма (1): контроллер setPerPage(validated(per_page, 20))» VS [stacks/php/mps-core/http.md § Requests] «IndexRequest: per_page between:1,100, page gt:0» (0.15)
C12. [stacks/php/review-checks.md § 5.2] «Таблица 5.2: $cache->getOrCreate вместо SmartCache/getOrSet — [ОШИБКА] (поправка: по версии utils)» VS [stacks/php/conventions.md § п.22] «getOrCreate/getCacheKeyByParts устаревшие только с utils ≥ 2.6.4; при 2.6.x — не находка» (0.7)
C13. [stacks/php/review-checks.md § 5.2] «Таблица 5.2: $cache->getOrCreate вместо SmartCache/getOrSet — [ОШИБКА] (поправка: по версии utils)» VS [stacks/php/mps-core/capabilities.md § Кеш и Redis] «utils ≥ 2.6.4 — getOrSet вместо getOrCreate (@deprecated); SmartCache — utils ≥ 2.8» (0.3)
C14. [stacks/php/review-checks.md § 5.2] «Workflow с записью — всегда в транзакции, включая одиночный create (O-8) — workflow-without-transaction» VS [stacks/php/checklist.md § п.19] «Workflow с записью — в одном TransactionManagerInterface::run(); без транзакции — только с причиной в design.md» (0.3)
C15. [stacks/php/review-checks.md § 5.2] «Workflow с записью — всегда в транзакции, включая одиночный create (O-8) — workflow-without-transaction» VS [stacks/php/review-tags.md § Таблица] «workflow-without-transaction: workflow пишет без run() и без причины в design.md (O-8)» (0.25)
C16. [stacks/php/review-findings.md § Enums / Models] «Enums/Models: описание класса и свойств (docblock) обязательны» VS [stacks/php/style.md § Шапка класса] «@param/@return — только если добавляют информацию сверх сигнатуры» (0.15)
