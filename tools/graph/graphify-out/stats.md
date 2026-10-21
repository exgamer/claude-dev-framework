# Граф правил — сводка

Узлов 1112, связей 4475, групп 11. Кандидаты ниже — подсказки, проверять по файлам.

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
- транзакции — 24
- возможности SDK/ядра — 23
- Конкурентность — 21
- RabbitMQ — 19
- Авторизация и права — 19
- теги ревью — 18
- HTTP точка входа — 18
- Подтверждение коммита/пуша — 18
- Swagger — 18
- DI — 18
- субагенты — 17
- Внешние интеграции — 17
- миграции БД — 17
- enum — 17
- Валидация — 17
- unit-тесты — 16
- безопасность — 16
- переиспользование хелперов — 16

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [architecture/layers.md § Направление зависимостей] «Common → ни от чего, кроме языка и библиотек дат» VS [architecture/layers.md § Общий бизнес-словарь] «Common без зависимостей, кроме языка, библиотеки дат и базового трейта enum ядра» (0.25)
C3. [architecture/layers.md § Зависимости] «Таблицы БД — всегда домену: нужен запрос — метод в репозитории того домена, чья таблица» VS [architecture/cross-domain.md § Правила] «Read-only подзапрос (EXISTS/JOIN) к таблице чужого домена в своём репозитории допустим с комментарием, чья таблица» (0.25)
C4. [stacks/go/checklist.md § п. 28] «Каждый хендлер скоупит по владельцу get/update/delete; скоуп — тип с явным All; чужая по ID — 404; id владельца из запроса — 403» VS [core/backlog.md § B-61] «B-61: эталон Go пишет Update(id) без скоупа против conventions.md п. 7a «скоуп при чтении и записи» — рекомендация: скоуп в условии записи» (0.7)
C5. [stacks/go/checklist.md § п. 36] «Doc-комментарий на публичных типах и полях сущностей — только если добавляет смысл к имени; формат «Имя Описание по-русски»» VS [core/backlog.md § B-67] «B-67: кандидаты графа — style.md против Go п. 13 (doc на сущности), Go п. 23a против async.md, capabilities.md против conventions.md, layers.md против cross-domain.md» (0.25)
C6. [stacks/go/checklist.md § п. 23a] «Consumer тонкий и идемпотентный; ошибка десериализации — ACK, временная — NACK; топология при старте через SDK» VS [core/backlog.md § B-67] «B-67: кандидаты графа — style.md против Go п. 13 (doc на сущности), Go п. 23a против async.md, capabilities.md против conventions.md, layers.md против cross-domain.md» (0.2)
C7. [stacks/go/checklist.md § п. 4] «Границы: page lte=10000, per_page lte=100; невалидный id пути — 422; правила в теге binding (validate SDK не читает)» VS [core/backlog.md § B-57] «B-57: oneof=KZT RUB USD в теге — дубль enum (enum-list-drift); нужно своё правило через CustomValidationRules()» (0.2)
C8. [core/principles.md § Повторяющаяся обработка — один хелпер] «Преобразование формата — один хелпер: SDK → проект → новый хелпер сразу, даже при одном вызове» VS [approaches/process/review-tags.md § Стиль и текст] «duplicated-format-logic: есть в SDK/хелпере — [ОШИБКА]; вторая копия — [ВНИМАНИЕ]; одно вхождение без хелпера — [ИНФО]» (0.2)
C9. [core/style.md § Комментарии] «Doc-комментарий на публичных типах/методах — только если есть что сказать сверх имени (comment-restates-name)» VS [stacks/go/conventions.md § Предупреждения] «Отсутствие doc-комментария на доменной сущности/модели или неочевидном поле — предупреждение» (0.3)
C10. [regulations/contracts.md § Моки в тестах] «Бизнес-сервисы: в Go не мокаем (конкретный тип, фейки репозиториев); в PHP — допустимо через ServiceInterface» VS [architecture/layers.md § Правила слоёв] «DI-контейнер — только в Bootstrap; резолв в бизнес-коде — di-resolve-in-business-code; PHP ServiceInterface — всегда, Go — конкретный тип» (0.2)
C11. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § Цель ревью, --mr п. 5, 7] «База ревью MR — Target Branch этого MR; без glab и без названной цели — stage» (0.15)
C12. [skills/dev-go/SKILL.md § Шаг 3, п. 1] «Источник SDK и версии из go.mod записываются в раздел «SDK» design.md (субагент — в итог оркестратору); без «SDK» код на SDK не начинать» VS [skills/dev-php/SKILL.md § Шаг 3] «Версия ядра — из composer.lock; нет справочника версии — собрать tools/sdk-ref/php.sh; запись версии в design.md не требуется» (0.2)
C13. [stacks/go/review-findings.md § Контекст, таймауты, ошибки] «В репозиториях — context.WithTimeout(ctx, 10*time.Second) или аналог» VS [stacks/go/go-sdk/infra/redis.md § Пример репозитория] «Каждый вызов Redis — свой короткий таймаут (2 с); действует только при REDIS_CONTEXT_TIMEOUT_ENABLED=true» (0.15)
C14. [stacks/php/checklist.md § п. 37] «Модель: @property по-русски, $table со схемой, $fillable, $casts [О]» VS [stacks/php/style.md § Классы] «Модели: @property на каждое поле, описание по-русски — у неочевидных; $table со схемой, public — без префикса» (0.2)
C15. [stacks/php/mps-core/capabilities.md § вводная] «При расхождении с этим файлом верна официальная дока нужной версии; метода нет в справочнике версии проекта — его нет» VS [stacks/php/conventions.md § Пакеты ядра] «Версия ядра — из composer.lock, у пользователя не спрашивать (O-4); API сверять с vendor/mps проекта, а не с примерами mps-core/*.md» (0.25)
C16. [approaches/process/review.md § За пределами дифа, после п. 7] «Уровень находки с тегом — только по каталогу тегов (O-24); нет тега — завести в каталоге + строка rules-log.md» VS [stacks/php/review-checks.md § Архитектурные нарушения] «Строка таблицы без тега — [ОШИБКА] (временно, до заведения тега, O-24); с тегом — по каталогу» (0.3)
C17. [stacks/php/review-checks.md § Архитектурные нарушения] «Новый HTTP-класс (Controller/Request/Response/Middleware) в Domains/ вместо Entrypoint/ — нарушение (поправка: путь Entrypoints/{Context}/{Domain}/{Module}, P-9)» VS [stacks/php/structure.md § Контексты] «Contexts/ регламента = Entrypoints/ (P-9); контексты Admin, Mobile, Integration, Lpm, Console, Queue; if ($isAdmin) запрещён — разные контроллеры» (0.2)
C18. [stacks/php/review-findings.md § Controller] «per_page задаётся явно (в IndexRequest/SearchRequest и в аннотации), не «по умолчанию где-то в ядре»» VS [stacks/php/mps-core/http.md § Requests] «IndexRequest: page min:0 max Page::MAX_PAGE, per_page sometimes between:1,MAX_PER_PAGE, id max ID_MAX» (0.2)
C19. [stacks/php/review-checks.md § Архитектурные нарушения] «Репозиторий без бизнес-логики и бизнес-исключений, в т.ч. NotFoundAppException — возвращает null, «не найдено» решает вызывающий» VS [stacks/php/structure.md § Go SDK ↔ Laravel] «Доменная модель в PHP — Eloquent-модель из Infrastructure (решение P-4); маппера нет, DTO → колонки явным списком в репозитории» (0.15)
