# Граф правил — сводка

Узлов 1165, связей 4685, групп 10. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- agents/df-analyst.md
- agents/df-architect.md
- agents/df-go.md
- agents/df-php.md
- agents/df-qa.md
- agents/df-review.md
- agents/df-security.md
- skills/dev-mr/SKILL.md
- stacks/go/go-sdk/main.md
- stacks/go/go-sdk/module.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 31
- возможности SDK/ядра — 28
- транзакции — 27
- Конкурентность — 24
- Ошибки и исключения — 23
- теги ревью — 22
- Авторизация и права — 21
- субагенты — 20
- безопасность — 20
- enum — 20
- RabbitMQ — 19
- workflow — 19
- unit-тесты — 18
- HTTP точка входа — 18
- Подтверждение коммита/пуша — 18
- валидация — 18
- Swagger — 18
- Command — 17
- хелперы и переиспользование — 17
- DI — 17

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [stacks/php/checklist.md § п. 12] «О: id → integer|gt:0|max bigint, id пути в Request без ограничения маршрута (0/-1/abc → 422); page min:0 (0 — первая), max Page::MAX_PAGE = 10000» VS [core/backlog.md § B-83] «page=0 или не передан → 1 при разборе запроса, до расчёта смещения (беззнаковое переполнение)» (0.2)
C3. [core/rules-evolution.md § Куда писать] «.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк» VS [core/principles.md § шапка] «При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»» (0.15)
C4. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § Цель ревью, --mr п. 5, 7] «База ревью MR — Target Branch этого MR; без glab и без названной цели — stage» (0.15)
C5. [stacks/go/examples/tariff/README.md § Граница примера] «Уведомление партнёра — после коммита без гарантии доставки (допущение примера); нужна гарантия — outbox» VS [approaches/process/review-tags.md § Транзакции, конкурентность, outbox] «side-effect-without-outbox [ОШИБКА]: эффект с гарантией доставки (платёжный путь — всегда) fire-and-forget вместо outbox» (0.2)
C6. [stacks/go/examples/tariff/README.md § healthcheck] «Пробы healthcheck: сбой — 503 без Sentry, причина — logger.Warning; путь /healthcheck/* вне схемы /api намеренно» VS [regulations/rest-api.md § URL] «URL: /api/<context>/<version>/<domain>/<module>/<entity>/<action?>; модуль = сущность не повторяется; kebab-case, множественное число» (0.15)
C7. [stacks/go/go-sdk/infra/postgres.md § Пагинация] «per_page не передан → умолчание хелпера 30; SetPerPage только при PerPage > 0 (иначе SDK подставит 20)» VS [stacks/php/review-findings.md § Controller] «per_page в пагинации задаётся явно, не «по умолчанию где-то в ядре»» (0.25)
C8. [stacks/go/structure.md § Entrypoint Layer, Консоль] «Консоль — свой bootstrap internal/console/bootstrap/{domain}/{module}/; серверный bootstrap в консоль не подключается» VS [stacks/go/structure.md § Bootstrap / Application Layer] «Одна настройка — одно место: env-переменную читает config.go владельца, остальные — через Load{Name}() (консоль импортирует серверный пакет ради LoadMaxAttempts)» (0.3)
C9. [architecture/layers.md § Общий бизнес-словарь] «Common — только enum, value-объекты, чистые функции; без зависимостей кроме языка, дат и трейта enum ядра; копия понятия — duplicated-enum, логика — common-with-logic» VS [stacks/go/structure.md § Слои и зависимости] «internal/common → только стандартная библиотека и сам common; тест границ boundary_test.go» (0.25)
C10. [stacks/php/checklist.md § п. 39] «О: прогнаны csfix-validate, phpstan (уровень ≥ 5, шум mps — ignoreErrors, не @phpstan-ignore), test:run; не запускал — так и написано» VS [stacks/php/checklist.md § п. 53] «В: новые @phpstan-ignore*, markTestSkipped, #[CodeCoverageIgnore], понижение level, ignoreErrors — только с причиной рядом (check-silenced)» (0.3)
C11. [stacks/go/conventions.md § п. 23] «Источник и версия Go SDK — из go.mod без вопроса; спросить пользователя для нового проекта/смены версии/нового пакета; записать в design.md «SDK» (O-4)» VS [stacks/php/conventions.md § Пакеты ядра] «Версия ядра mps/core — из composer.lock, у пользователя не спрашивать (O-4)» (0.2)
C12. [architecture/layers.md § Роли и права на операцию] «Роли/права на операцию — именованная middleware на маршруте; права по модулю {подпроект}.{context}.{module}, по действиям (.view/.create/.update) — только если чтение и запись разделены по ролям» VS [stacks/php/examples/README.md § Entrypoints/Admin/Billing] «Права — permission:parking.admin.tariffs.{view|create|update} на каждом маршруте (чтение и запись различаются, п. 19a)» (0.3)
C13. [stacks/php/checklist.md § п. 10] «О: устаревшее API ядра (RabbitManager::putIn, QueryFilters V1, FileManager::upload; getOrCreate при utils ≥ 2.6.4) — нет» VS [stacks/php/review-tags.md § таблица] «deprecated-core-api [ОШИБКА]: RabbitManager::putIn, QueryFilters V1, FileManager::upload, setMessagePrefix; getOrCreate/getCacheKeyByParts при utils ≥ 2.6.4» (0.3)
C14. [core/backlog.md § B-87] «Служебные пути оркестратора (/healthcheck/*, метрики) — не ресурс API, под схему /api/… не подпадают» VS [stacks/php/structure.md § Контексты] «Healthcheck не пишется: HealthCheck из mps/utils (GET /health/liveness, /health/readiness)» (0.2)
