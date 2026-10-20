# Граф правил — сводка

Узлов 1082, связей 4436, групп 11. Кандидаты ниже — подсказки, проверять по файлам.

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
- репозиторий — 25
- транзакции — 25
- возможности SDK/ядра — 24
- Конкурентность — 22
- RabbitMQ — 19
- Авторизация и права — 19
- enum — 19
- теги ревью — 18
- Swagger — 18
- DI — 18
- unit-тесты — 17
- HTTP точка входа — 17
- Внешние интеграции — 17
- миграции БД — 17
- Подтверждение коммита/пуша — 17
- безопасность — 16
- переиспользование хелперов — 16
- Валидация — 16
- слои — 15

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [approaches/process/review.md § Сохранение] «Отчёт — файл review_result/{YYYY-MM-DD_HH-MM}_{type}.md в корне проекта («Один формат»)» VS [agents/df-review.md § строка 9] «Ревьюер-субагент код не правит; пишет только docs/tasks/<ключ>/review.md (и через Bash — нет)» (0.3)
C3. [approaches/process/self-check.md § п.9] «lint.py фреймворка: каждая находка в своём коде исправлена до сдачи; база разработчика — merge-base с release, ревьюера — stage; чужое/старое — «унаследовано»; анализатор не заменяет памятку» VS [core/backlog.md § D-1] «D-1: ревьюер при прогоне анализатора без находок проверяет глазами только «Остаток — агенту» из RULES.md» (0.2)
C4. [architecture/layers.md § Направление зависимостей] «Common → ни от чего, кроме языка и библиотек дат» VS [architecture/layers.md § Общий бизнес-словарь] «Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра» (0.25)
C5. [architecture/layers.md § Зависимости] «Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица» VS [architecture/cross-domain.md § Правила] «Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица» (0.25)
C6. [core/principles.md § Повторяющаяся обработка — один хелпер] «Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове» VS [approaches/process/review-tags.md § Стиль и текст] «duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]» (0.2)
C7. [core/style.md § Комментарии] «Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени (comment-restates-name)» VS [stacks/go/conventions.md § Предупреждения] «Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение» (0.3)
C8. [regulations/contracts.md § Моки в тестах] «Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface» VS [architecture/layers.md § Правила слоёв] «DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип» (0.2)
C9. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § --mr п. 5, 7] «База ревью MR — Target Branch этого MR; не названа — stage (regulations/git.md)» (0.15)
C10. [skills/dev-review/SKILL.md § Шаг 2] «Уровень — только из каталога: новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]» VS [approaches/process/review.md § Порядок п. 3a] «Унаследованное нарушение (нет в diff) — [ВНИМАНИЕ] «унаследовано» + строка в rules-log, не [ОШИБКА] и не повод для круга; безопасность — по каталогу всегда» (0.2)
C11. [skills/dev-review/SKILL.md § Шаг 4] «Итог: путь, вердикт, предложение исправить [ОШИБКИ]; коммит/пуш — по явному подтверждению» VS [agents/df-review.md § п. 4] «Ревьюер-субагент исправления не предлагает — их назначает оркестратор» (0.2)
C12. [skills/dev-rule/SKILL.md § Шаг 3а п. 1] «Эталон Go проверяется: go build && go vet && go test ./... && gofmt -l . && golangci-lint run ./...; PHP — tools/php-check/run.sh; не запускал — так и написано» VS [stacks/go/examples/tariff/README.md § вступление, Линтер] «Проверка эталона: go build && go vet && go test -race ./... && gofmt -l .; линтер — golangci-lint run ./... (v2)» (0.25)
C13. [stacks/go/checklist.md § п. 28] «п. 28: каждый хендлер скоупит по владельцу отдельно get/update/delete; скоуп — тип с явным All (ParkingScope); чужая по ID — 404, id владельца из запроса — 403» VS [stacks/go/examples/tariff/README.md § infrastructure/postgres/billing/tariff] «Update/SetDefault в репозитории — deleted_at IS NULL и RowsAffected == 0 → ErrTariffNotFound; скоуп parking_id IN ? — только в GetByID и Paginated» (0.75)
C14. [stacks/go/review-findings.md § Контекст, таймауты, ошибки] «В репозиториях — context.WithTimeout(ctx, 10*time.Second) или аналог» VS [stacks/go/go-sdk/infra/redis.md § Пример репозитория] «Каждый вызов Redis — свой короткий таймаут (2 с); действует только при REDIS_CONTEXT_TIMEOUT_ENABLED=true» (0.15)
C15. [stacks/php/checklist.md § п. 37] «Модель: @property по-русски, $table со схемой, $fillable, $casts [О]» VS [stacks/php/style.md § Классы] «Модели: @property на каждое поле, описание по-русски — у неочевидных; $table со схемой, public — без префикса» (0.2)
C16. [stacks/go/checklist.md § п. 28] «п. 28: каждый хендлер скоупит по владельцу отдельно get/update/delete; скоуп — тип с явным All (ParkingScope); чужая по ID — 404, id владельца из запроса — 403» VS [stacks/php/examples/README.md § Infrastructure/Postgres/Billing] «updateFromDto(int $id, TariffDto) — один UPDATE … WHERE через whereKey()->update(); скоуп — в findById/getByIdInParkings» (0.3)
C17. [stacks/go/examples/tariff/README.md § Событие tariff.created] «tariff.created: все поля обязательны, null нет (grace_minutes uint); подтверждение брокера; at-least-once, дедупликация по event_id» VS [stacks/php/examples/README.md § Событие tariff.created] «tariff.created: null только у grace_minutes; подтверждения брокера ядро не ждёт; at-least-once, дедупликация по event_id; расхождение с Go — особенность примера» (0.25)
C18. [stacks/go/examples/tariff/README.md § healthcheck] «Healthcheck: /healthcheck/* вне схемы /api/<context>/<version>; сбой — 503 без Sentry, причина — logger.Warning» VS [stacks/php/examples/README.md § вступление] «Healthcheck — готовый MPS\Utils HealthCheck (/health/liveness, /health/readiness), своего нет» (0.2)
C19. [stacks/php/mps-core/capabilities.md § вводная] «При расхождении с этим файлом верна официальная дока нужной версии; метода нет в справочнике версии проекта — его нет» VS [stacks/php/conventions.md § Пакеты ядра] «Версия ядра — из composer.lock, у пользователя не спрашивать (O-4); API сверять с vendor/mps проекта, а не с примерами mps-core/*.md» (0.25)
C20. [approaches/process/review.md § Порядок, после п. 7] «Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести в каталоге + строка в rules-log» VS [stacks/php/review-checks.md § Архитектурные нарушения] «Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу» (0.3)
C21. [stacks/php/review-checks.md § Архитектурные нарушения] «Новый HTTP-класс (Controller/Request/Response/Middleware) в Domains/ вместо Entrypoint/ — нарушение (поправка: путь Entrypoints/{Context}/{Domain}/{Module}, P-9)» VS [stacks/php/structure.md § Контексты] «Contexts/ регламента = Entrypoints/ (P-9); контексты Admin, Mobile, Integration, Lpm, Console, Queue; if ($isAdmin) запрещён — разные контроллеры» (0.2)
C22. [stacks/php/review-findings.md § Controller] «per_page задаётся явно (в IndexRequest/SearchRequest и в аннотации), не «по умолчанию где-то в ядре»» VS [stacks/php/mps-core/http.md § Requests] «IndexRequest: page min:0 max Page::MAX_PAGE, per_page sometimes between:1,MAX_PER_PAGE, id max ID_MAX» (0.2)
C23. [stacks/php/review-checks.md § Архитектурные нарушения] «Репозиторий без бизнес-логики и бизнес-исключений, в т.ч. NotFoundAppException — возвращает null, «не найдено» решает вызывающий» VS [stacks/php/structure.md § Go SDK ↔ Laravel] «Доменная модель в PHP — Eloquent-модель из Infrastructure (решение P-4); маппера нет, DTO → колонки явным списком в репозитории» (0.15)
