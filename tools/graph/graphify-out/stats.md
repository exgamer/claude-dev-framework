# Граф правил — сводка

Узлов 1058, связей 4289, групп 11. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- agents/df-analyst.md
- agents/df-architect.md
- agents/df-go.md
- agents/df-php.md
- agents/df-qa.md
- agents/df-review.md
- agents/df-security.md
- skills/dev-mr/SKILL.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- Ошибки и исключения — 25
- репозиторий — 24
- транзакции — 24
- возможности SDK/ядра — 22
- Конкурентность — 21
- RabbitMQ — 19
- Авторизация и права — 19
- enum — 19
- Swagger — 18
- DI — 18
- теги находок — 17
- HTTP точка входа — 17
- Внешние интеграции — 17
- unit-тесты — 16
- безопасность — 16
- хелперы/переиспользование — 16
- Валидация — 16
- субагенты — 15
- миграции БД — 15
- Подтверждение коммита/пуша — 15

## Кандидаты в противоречия (conflicts_with)
C1. [agents/df-review.md § п. 2] «Круг N: памятка, security.md и каталоги тегов — всегда; прочие правила — по слоям изменённых файлов» VS [approaches/process/review.md § Независимость] «В цепочке /dev ревью — отдельный субагент без контекста разработки, только чтение; ревьюятся код и тесты» (0.1)
C2. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C3. [architecture/layers.md § Направление зависимостей] «Common → ни от чего, кроме языка и библиотек дат» VS [architecture/layers.md § Общий бизнес-словарь] «Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра» (0.25)
C4. [architecture/layers.md § Зависимости] «Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица» VS [architecture/cross-domain.md § Правила] «Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица» (0.25)
C5. [core/backlog.md § Исправить B-61] «B-61: эталон Go пишет Update(id)/SetDefault по id без скоупа, а conventions.md п. 7a требует скоуп при чтении и записи» VS [stacks/go/examples/tariff/README.md § infrastructure/postgres/billing/tariff] «Update/SetDefault в репозитории — deleted_at IS NULL и RowsAffected == 0 → ErrTariffNotFound; скоуп parking_id IN ? — только в GetByID и Paginated» (0.8)
C6. [core/backlog.md § Исправить B-57] «B-57: значения enum в теге oneof=KZT RUB USD и lte=1440 дублируют enum/константы (enum-list-drift) — своё правило через CustomValidationRules()» VS [stacks/go/checklist.md § п. 4] «п. 4: id > 0 (невалидный id пути — 422), page lte=10000, per_page lte=100, правила в теге binding (validate SDK не читает); права — RequirePermissionMiddleware на маршруте» (0.2)
C7. [core/principles.md § Повторяющаяся обработка — один хелпер] «Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове» VS [approaches/process/review-tags.md § Стиль и текст] «duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]» (0.2)
C8. [core/style.md § Комментарии] «Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени (comment-restates-name)» VS [stacks/go/conventions.md § Предупреждения] «Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение» (0.3)
C9. [regulations/contracts.md § Моки в тестах] «Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface» VS [architecture/layers.md § Правила слоёв] «DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип» (0.2)
C10. [skills/dev-rule/SKILL.md § Шаг 3а п. 1] «Эталон Go проверяется: go build && go vet && go test ./... && gofmt -l . && golangci-lint run ./...; PHP — tools/php-check/run.sh; не запускал — так и написано» VS [stacks/go/examples/tariff/README.md § вступление, Линтер] «Проверка эталона: go build && go vet && go test -race ./... && gofmt -l .; линтер — golangci-lint run ./... (v2)» (0.25)
C11. [stacks/go/checklist.md § п. 28] «п. 28: каждый хендлер скоупит по владельцу отдельно get/update/delete; скоуп — тип с явным All (ParkingScope); чужая по ID — 404, id владельца из запроса — 403» VS [stacks/go/examples/tariff/README.md § infrastructure/postgres/billing/tariff] «Update/SetDefault в репозитории — deleted_at IS NULL и RowsAffected == 0 → ErrTariffNotFound; скоуп parking_id IN ? — только в GetByID и Paginated» (0.75)
C12. [stacks/go/review-findings.md § Контекст, таймауты, ошибки] «В репозиториях — context.WithTimeout(ctx, 10*time.Second) или аналог» VS [stacks/go/go-sdk/infra/redis.md § Пример репозитория] «Каждый вызов Redis — свой короткий таймаут (2 с); действует только при REDIS_CONTEXT_TIMEOUT_ENABLED=true» (0.15)
C13. [stacks/php/checklist.md § п. 37] «Модель: @property по-русски, $table со схемой, $fillable, $casts [О]» VS [stacks/php/style.md § Классы] «Модели: @property на каждое поле, описание по-русски — у неочевидных; $table со схемой, public — без префикса» (0.2)
C14. [stacks/go/checklist.md § п. 28] «п. 28: каждый хендлер скоупит по владельцу отдельно get/update/delete; скоуп — тип с явным All (ParkingScope); чужая по ID — 404, id владельца из запроса — 403» VS [stacks/php/examples/README.md § Infrastructure/Postgres/Billing] «updateFromDto(int $id, TariffDto) — один UPDATE … WHERE через whereKey()->update(); скоуп — в findById/getByIdInParkings» (0.3)
C15. [stacks/go/examples/tariff/README.md § Событие tariff.created] «tariff.created: все поля обязательны, null нет (grace_minutes uint); подтверждение брокера; at-least-once, дедупликация по event_id» VS [stacks/php/examples/README.md § Событие tariff.created] «tariff.created: null только у grace_minutes; подтверждения брокера ядро не ждёт; at-least-once, дедупликация по event_id; расхождение с Go — особенность примера» (0.25)
C16. [stacks/go/examples/tariff/README.md § healthcheck] «Healthcheck: /healthcheck/* вне схемы /api/<context>/<version>; сбой — 503 без Sentry, причина — logger.Warning» VS [stacks/php/examples/README.md § вступление] «Healthcheck — готовый MPS\Utils HealthCheck (/health/liveness, /health/readiness), своего нет» (0.2)
C17. [stacks/php/mps-core/capabilities.md § вводная] «При расхождении с этим файлом верна официальная дока нужной версии; метода нет в справочнике версии проекта — его нет» VS [stacks/php/conventions.md § Пакеты ядра] «Версия ядра — из composer.lock, у пользователя не спрашивать (O-4); API сверять с vendor/mps проекта, а не с примерами mps-core/*.md» (0.25)
C18. [approaches/process/review.md § За пределами дифа, абзац уровней] «Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести тег в каталоге + строка в rules-log» VS [stacks/php/review-checks.md § Архитектурные нарушения] «Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу» (0.3)
C19. [approaches/process/review.md § За пределами дифа — общее для стеков] «При --all из проверок за пределами дифа — только механические grep (запросы вне репозитория, формат, индекс массива, константы вместо enum, копии enum/импорт чужого домена)» VS [stacks/php/review-checks.md § п. 0b] «0b индекс массива без проверки (array-key-unchecked) — по файлам дифа; phpstan его не заменяет» (0.25)
C20. [stacks/php/review-checks.md § Архитектурные нарушения] «Новый HTTP-класс (Controller/Request/Response/Middleware) в Domains/ вместо Entrypoint/ — нарушение (поправка: путь Entrypoints/{Context}/{Domain}/{Module}, P-9)» VS [stacks/php/structure.md § Контексты] «Contexts/ регламента = Entrypoints/ (P-9); контексты Admin, Mobile, Integration, Lpm, Console, Queue; if ($isAdmin) запрещён — разные контроллеры» (0.2)
C21. [stacks/php/review-findings.md § Controller] «per_page задаётся явно (в IndexRequest/SearchRequest и в аннотации), не «по умолчанию где-то в ядре»» VS [stacks/php/mps-core/http.md § Requests] «IndexRequest: page min:0 max Page::MAX_PAGE, per_page sometimes between:1,MAX_PER_PAGE, id max ID_MAX» (0.2)
C22. [stacks/php/review-checks.md § Архитектурные нарушения] «Репозиторий без бизнес-логики и бизнес-исключений, в т.ч. NotFoundAppException — возвращает null, «не найдено» решает вызывающий» VS [stacks/php/structure.md § Go SDK ↔ Laravel] «Доменная модель в PHP — Eloquent-модель из Infrastructure (решение P-4); маппера нет, DTO → колонки явным списком в репозитории» (0.15)
