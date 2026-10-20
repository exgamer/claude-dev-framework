# Граф правил — сводка

Узлов 1037, связей 4169, групп 12. Кандидаты ниже — подсказки, проверять по файлам.

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
- транзакции — 24
- Ошибки и исключения — 23
- репозиторий — 22
- возможности SDK/ядра — 21
- Конкурентность — 21
- RabbitMQ — 18
- Авторизация и права — 18
- теги находок — 17
- HTTP точка входа — 17
- Внешние интеграции — 17
- enum — 17
- DI — 17
- unit-тесты — 16
- Swagger — 16
- субагенты — 15
- хелперы/переиспользование — 15
- подтверждение действий с БД — 14
- миграции БД — 14
- Подтверждение коммита/пуша — 14
- именование — 14

## Кандидаты в противоречия (conflicts_with)
C1. [agents/df-review.md § п. 2] «Круг N: памятка, security.md и каталоги тегов — всегда; прочие правила — по слоям изменённых файлов» VS [approaches/process/review.md § Независимость] «В цепочке /dev ревью — отдельный субагент без контекста разработки, только чтение; ревьюятся код и тесты» (0.1)
C2. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C3. [architecture/layers.md § Направление зависимостей] «Common → ни от чего, кроме языка и библиотек дат» VS [architecture/layers.md § Общий бизнес-словарь] «Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра» (0.25)
C4. [architecture/layers.md § Зависимости] «Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица» VS [architecture/cross-domain.md § Правила] «Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица» (0.25)
C5. [core/principles.md § Повторяющаяся обработка — один хелпер] «Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове» VS [approaches/process/review-tags.md § Стиль и текст] «duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]» (0.2)
C6. [core/style.md § Комментарии] «Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени (comment-restates-name)» VS [stacks/go/conventions.md § Предупреждения] «Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение» (0.3)
C7. [regulations/contracts.md § Моки в тестах] «Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface» VS [architecture/layers.md § Правила слоёв] «DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип» (0.2)
C8. [skills/dev-rule/SKILL.md § Шаг 3а, п. 2] «Граф и кэш правил коммитятся вместе с правкой» VS [core/decisions.md § O-22] «O-22 (открыт): граф кода проекта не введён; в варианте б/в граф не коммитится, обновляет координатор» (0.15)
C9. [stacks/go/checklist.md § п. 29] «Новый роут без auth-middleware — только осознанно, с причиной в MR (О)» VS [approaches/process/security.md § Каталог тегов безопасности] «missing-auth-middleware — [БЕЗОПАСНОСТЬ]; межсервисный роут без auth исторически — вопрос «осознанно?» в MR» (0.25)
C10. [stacks/go/checklist.md § п. 46] «Преобразование формата — один раз: SDK → pkg/; вторая копия — вынести (В); inline при готовом — О» VS [approaches/process/review-tags.md § Стиль и текст] «duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]» (0.2)
C11. [stacks/go/checklist.md § п. 23a] «Consumer тонкий и идемпотентный; ошибка десериализации — ACK, временная — NACK; топология — при старте через SDK» VS [approaches/patterns/async.md § Consumer] «Ручные ack/nack, где результат обработчика их уже выражает, не пишутся» (0.15)
C12. [stacks/go/go-sdk/entrypoint/http.md § routes.go] «URL — /api/<context>/<version>/<domain>/<module>/<entity> (модуль, совпадающий с сущностью, не повторяется)» VS [stacks/go/examples/tariff/README.md § entrypoints/admin/http/billing/tariff] «URL группы — /api/<context>/<version>/<domain>/<entity> (модуль, совпадающий с сущностью, не повторяется)» (0.6)
C13. [stacks/go/review-findings.md § Контекст, таймауты, ошибки] «В репозиториях — context.WithTimeout(ctx, 10*time.Second) или аналог» VS [stacks/go/go-sdk/infra/redis.md § Пример репозитория] «Каждый вызов Redis — свой короткий таймаут (2 с); действует только при REDIS_CONTEXT_TIMEOUT_ENABLED=true» (0.15)
C14. [stacks/php/checklist.md § п. 37] «Модель: @property по-русски, $table со схемой, $fillable, $casts [О]» VS [stacks/php/style.md § Классы] «Модели: @property на каждое поле, описание по-русски — у неочевидных; $table со схемой, public — без префикса» (0.2)
C15. [stacks/php/mps-core/capabilities.md § вводная] «При расхождении с этим файлом верна официальная дока нужной версии; метода нет в справочнике версии проекта — его нет» VS [stacks/php/conventions.md § Пакеты ядра] «Версия ядра — из composer.lock, у пользователя не спрашивать (O-4); API сверять с vendor/mps проекта, а не с примерами mps-core/*.md» (0.25)
C16. [approaches/process/review.md § За пределами дифа, абзац уровней] «Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести тег в каталоге + строка в rules-log» VS [stacks/php/review-checks.md § Архитектурные нарушения] «Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу» (0.3)
C17. [approaches/process/review.md § За пределами дифа — общее для стеков] «При --all из проверок за пределами дифа — только механические grep (запросы вне репозитория, формат, индекс массива, константы вместо enum, копии enum/импорт чужого домена)» VS [stacks/php/review-checks.md § п. 0b] «0b индекс массива без проверки (array-key-unchecked) — по файлам дифа; phpstan его не заменяет» (0.25)
C18. [stacks/php/review-checks.md § Архитектурные нарушения] «Новый HTTP-класс (Controller/Request/Response/Middleware) в Domains/ вместо Entrypoint/ — нарушение (поправка: путь Entrypoints/{Context}/{Domain}/{Module}, P-9)» VS [stacks/php/structure.md § Контексты] «Contexts/ регламента = Entrypoints/ (P-9); контексты Admin, Mobile, Integration, Lpm, Console, Queue; if ($isAdmin) запрещён — разные контроллеры» (0.2)
C19. [stacks/php/review-findings.md § Controller] «per_page задаётся явно (в IndexRequest/SearchRequest и в аннотации), не «по умолчанию где-то в ядре»» VS [stacks/php/mps-core/http.md § Requests] «IndexRequest: page min:0 max Page::MAX_PAGE, per_page sometimes between:1,MAX_PER_PAGE, id max ID_MAX» (0.2)
C20. [stacks/php/review-checks.md § Архитектурные нарушения] «Репозиторий без бизнес-логики и бизнес-исключений, в т.ч. NotFoundAppException — возвращает null, «не найдено» решает вызывающий» VS [stacks/php/structure.md § Go SDK ↔ Laravel] «Доменная модель в PHP — Eloquent-модель из Infrastructure (решение P-4); маппера нет, DTO → колонки явным списком в репозитории» (0.15)
