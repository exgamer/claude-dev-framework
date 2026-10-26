# Граф правил — сводка

Узлов 1330, связей 5291, групп 8. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- skills/dev-intro/SKILL.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- транзакции — 28
- возможности SDK/ядра — 27
- репозиторий — 27
- Конкурентность — 25
- слои — 25
- Swagger — 25
- DI — 24
- подтверждение коммита/пуша — 23
- unit-тесты — 22
- Ошибки и исключения — 22
- лог отклонений — 22
- теги находок — 21
- доступ и права — 21
- валидация — 21
- субагенты — 20
- Миграции БД — 20
- безопасность — 19
- HTTP точка входа — 19
- хелперы и переиспользование — 19
- workflow — 18

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [approaches/process/review-tags.md § Слои и место кода] «url-not-by-scheme [ВНИМАНИЕ]: путь нового эндпойнта по схеме /api/<context>/<version>/<domain>/<module>/<entity>/<action?>; исключения для служебных путей нет» VS [stacks/go/structure.md § Entrypoint Layer] «{context} не может быть internal; consumer событий — consumer, вебхуки — integration; служебные — system/http/healthcheck (G-4)» (0.25)
C3. [stacks/go/checklist.md § п. 29] «[ОШИБКА] Новый роут без auth-middleware — только осознанно, с причиной в MR» VS [stacks/go/security.md § таблица] «Новый route без auth где соседи требуют — missing-auth-middleware; admin-действие в client-группе — прямая [БЕЗОПАСНОСТЬ]» (0.6)
C4. [stacks/go/conventions.md § п. 7] «Хендлер — валидация (тег binding, validate SDK не читает), маппинг, вызов; права — middleware; исключение: create сверяет parking_id из тела со скоупом — 403» VS [approaches/process/security.md § Режим design, cross-scope-reference] «id связанной записи вне скоупа или несуществующий — 422 по полю, один текст; id владельца скоупа (parking_id) — 403 в middleware» (0.3)
C5. [stacks/go/review-findings.md § HTTP-роуты] «Новый роут без auth — причина в MR, нет — вопрос «осознанно?»; admin-действие в client без auth — [БЕЗОПАСНОСТЬ]» VS [stacks/go/security.md § таблица] «Новый route без auth где соседи требуют — missing-auth-middleware; admin-действие в client-группе — прямая [БЕЗОПАСНОСТЬ]» (0.3)
C6. [stacks/php/checklist.md § п. 18] «Два фильтра по одной колонке: умолчание производного — текстом, без default в схеме» VS [approaches/process/review-tags.md § Производительность] «list-filters-conflict [ВНИМАНИЕ]: умолчание производного фильтра — default в схеме OpenAPI, а не текстом в описании» (0.65)
C7. [stacks/php/mps-core/jobs.md § Обработчик RabbitMQ] «Повторная доставка безопасна: обработчик проверяет текущий статус и выходит, если уже обработано» VS [stacks/php/mps-core/transactions.md § Где НЕ нужна] «Транзакция не нужна для одного модифицирующего запроса и чтения; не защищает от дублей RabbitMQ» (0.1)
C8. [stacks/php/mps-core/model.md § начало] «@property — описание только у неочевидных полей; @author как у любого класса» VS [stacks/php/review-findings.md § Enums / Models] «Docblock класса и @property на каждое поле модели обязательны; описание — у неочевидных» (0.2)
C9. [stacks/php/mps-core/transactions.md § По умолчанию] «CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно» VS [stacks/php/mps-core/service.md § Простой сервис] «CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)» (0.3)
