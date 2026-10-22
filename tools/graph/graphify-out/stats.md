# Граф правил — сводка

Узлов 1236, связей 4901, групп 11. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- skills/dev-intro/SKILL.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- транзакции — 27
- Конкурентность — 25
- возможности SDK/ядра — 23
- репозиторий — 23
- слои — 22
- unit-тесты — 20
- теги находок — 20
- подтверждение коммита/пуша — 20
- Swagger — 20
- Ошибки и исключения — 19
- доступ и права — 19
- лог отклонений — 19
- именование — 19
- субагенты — 18
- валидация — 18
- безопасность — 17
- Миграции БД — 17
- DI — 17
- карта связей — 16
- подтверждение действий с БД — 16

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [regulations/git.md § Ветки] «Рабочая ветка от release, hotfix от production; без release — от main» VS [core/backlog.md § B-115] «B-115: review.md упоминает ветку preprod, которой нет в потоке git.md» (0.25)
C3. [skills/dev-rule/SKILL.md § Перезапуск сессии] «usage.py --project — папка, в которой начата сессия (при перезапуске)» VS [skills/dev-rule/SKILL.md § Шаг 4] «Шаг 4: usage.py --project <корень репозитория-источника>» (0.75)
C4. [stacks/go/review-findings.md § HTTP-роуты] «Новый роут без auth — причина в MR, нет — вопрос «осознанно?»; admin-действие в client без auth — [БЕЗОПАСНОСТЬ]» VS [stacks/go/security.md § таблица] «Новый route без auth где соседи требуют — missing-auth-middleware; admin-действие в client-группе — прямая [БЕЗОПАСНОСТЬ]» (0.3)
C5. [stacks/php/checklist.md § п. 18] «Два фильтра по одной колонке: умолчание производного — текстом, без default в схеме» VS [approaches/process/review-tags.md § Производительность, кэш, списки] «list-filters-conflict: умолчание производного фильтра — default в схеме OpenAPI, а не текстом (читается как предписание, B-109)» (0.65)
C6. [stacks/php/mps-core/domain.md § ServiceProvider модуля] «ServiceProvider модуля — только если модуль регистрирует что-то сверх definitions.php домена; у большинства нет» VS [stacks/php/structure.md § Биндинги DI] «Биндинги DI — только в definitions.php (полный перечень мест); биндинг вне — di-outside-definitions» (0.15)
C7. [stacks/php/mps-core/http.md § Requests] «IndexRequest: page sometimes min:0 max Page::MAX_PAGE; per_page sometimes between:1,MAX_PER_PAGE» VS [approaches/process/review-tags.md § Производительность, кэш, списки] «Параметры списка: page 1, per_page 30, page >10000 или отрицательный → 422» (0.2)
C8. [stacks/php/mps-core/jobs.md § Обработчик RabbitMQ] «Повторная доставка безопасна: обработчик проверяет текущий статус и выходит, если уже обработано» VS [stacks/php/mps-core/transactions.md § Где НЕ нужна] «Транзакция не нужна для одного модифицирующего запроса и чтения; не защищает от дублей RabbitMQ» (0.1)
C9. [stacks/php/mps-core/model.md § начало] «@property — описание только у неочевидных полей; @author как у любого класса» VS [stacks/php/review-findings.md § Enums / Models] «Docblock класса и @property на каждое поле модели обязательны; описание — у неочевидных» (0.2)
C10. [stacks/php/mps-core/transactions.md § По умолчанию] «CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно» VS [stacks/php/mps-core/service.md § Простой сервис] «CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)» (0.3)
