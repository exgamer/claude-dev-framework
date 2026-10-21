# Граф правил — сводка

Узлов 1128, связей 4524, групп 10. Кандидаты ниже — подсказки, проверять по файлам.

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
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 31
- транзакции — 29
- Конкурентность — 24
- возможности SDK/ядра — 23
- Ошибки и исключения — 22
- теги ревью — 21
- Внешние интеграции — 20
- именование — 20
- Авторизация и права — 19
- слои — 19
- безопасность — 18
- RabbitMQ — 18
- Подтверждение коммита/пуша — 18
- субагенты — 17
- enum — 17
- переиспользование хелперов — 17
- Swagger — 17
- миграции БД — 16
- Валидация — 16
- оркестратор /dev — 15

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [architecture/layers.md § Общий бизнес-словарь] «Common — только enum, value-объекты, чистые функции; без DI и I/O (кроме языка, дат и трейта enum ядра); копия — duplicated-enum» VS [architecture/layers.md § Направление зависимостей] «Common → ни от чего, кроме языка и библиотек дат (схема зависимостей)» (0.3)
C3. [regulations/contracts.md § Моки в тестах] «Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface» VS [architecture/layers.md § Правила слоёв] «DI только в сборке; резолв в бизнес-коде — di-resolve-in-business-code; Go — сервисы конкретным типом, PHP — ServiceInterface всегда» (0.2)
C4. [regulations/entity.md § Переход состояния] «Массовый переход — один UPDATE без загрузки сущностей, условие то же, что CanArchive()/canArchive() модели, своя Command» VS [stacks/go/examples/tariff/README.md § set_default_tariff_command.go] «Переход «по умолчанию» — POST /tariffs/:id/set-default, своя Command; 0 строк → ErrTariffStateChanged → 422» (0.2)
C5. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § Цель ревью, --mr п. 5, 7] «База ревью MR — Target Branch этого MR; без glab и без названной цели — stage» (0.15)
C6. [stacks/go/checklist.md § п. 36] «Doc-комментарий на публичных типах — только если добавляет смысл к имени, формат «Имя Описание по-русски»» VS [stacks/php/review-findings.md § Enums / Models] «Docblock класса и @property на каждое поле модели обязательны; описание — у неочевидных» (0.2)
C7. [stacks/go/conventions.md § Предупреждения] «Предупреждение: доменная сущность/модель без doc-комментария на структуре — предупреждение» VS [core/style.md § Комментарии] «Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени; PHP docblock класса — всегда» (0.3)
C8. [stacks/go/examples/tariff/README.md § Событие tariff.created] «tariff.created: все поля обязательны, null нет (grace_minutes uint); подтверждение брокера; at-least-once» VS [stacks/php/examples/README.md § Событие tariff.created] «tariff.created: null только у grace_minutes; подтверждения брокера ядро не ждёт; расхождение с Go — особенность примера» (0.7)
C9. [stacks/go/examples/tariff/README.md § healthcheck] «Путь /healthcheck/* — вне схемы /api/<context>/<version>/… намеренно; сбой — 503 без Sentry» VS [stacks/php/examples/README.md § вступление] «Healthcheck — готовый MPS\Utils HealthCheck (/health/liveness, /health/readiness), своего нет» (0.15)
C10. [stacks/go/go-sdk/domain.md § repository.go] «Пример Repository.GetByID(ctx, id) — без аргумента скоупа» VS [stacks/go/conventions.md § п. 7a] «п.7a: скоуп — ParkingScope{All bool; IDs []uint}; nil []uint = «все» запрещён; 2+ домена — в internal/common/» (0.25)
C11. [stacks/go/go-sdk/infra/postgres.md § Пагинация] «per_page не передан → умолчание хелпера 30; SetPerPage только при PerPage > 0 (иначе SDK подставит 20)» VS [stacks/php/review-findings.md § Controller] «per_page в пагинации задаётся явно, не «по умолчанию где-то в ядре»» (0.25)
C12. [stacks/go/review-findings.md § Контекст, таймауты, ошибки] «В репозиториях — context.WithTimeout(ctx, 10*time.Second) или аналог» VS [stacks/go/go-sdk/infra/redis.md § Пример репозитория] «Каждый вызов Redis — свой короткий таймаут (2 с); действует только при REDIS_CONTEXT_TIMEOUT_ENABLED=true» (0.15)
C13. [stacks/php/checklist.md § п. 37] «п.37: модель — @property по-русски (О), $table со схемой, $fillable, $casts» VS [core/style.md § Комментарии] «Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени; PHP docblock класса — всегда» (0.25)
C14. [stacks/php/review-checks.md § Архитектурные нарушения] «Строка таблицы без тега — [ОШИБКА] временно, до заведения тега (O-24)» VS [stacks/php/review-tags.md § вводная] «Уровень находки — только в каталогах (O-24): новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]» (0.3)
C15. [stacks/php/review-tags.md § таблица] «controller-auth-via-facade — [ОШИБКА]: Auth::id()/auth() в глубине сервиса вместо $request->user() в контроллере» VS [stacks/php/conventions.md § HTTP п.19a] «Права и контекст — в middleware, не в сервисе; hasRole/authorize/Gate в контроллере, сервисе, workflow — нет; чужая запись по id — 404» (0.2)
C16. [stacks/php/review-checks.md § Архитектурные нарушения] «Бизнес-логика и бизнес-исключения в Model/Repository — нарушение; репозиторий возвращает null, «не найдено» решает вызывающий» VS [stacks/php/structure.md § Go SDK ↔ Laravel] «Доменная модель в PHP — Eloquent-модель из Infrastructure (решение P-4); маппера нет, DTO → колонки явным списком в репозитории» (0.15)
