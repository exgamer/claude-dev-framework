# Граф правил — сводка

Узлов 1100, связей 4345, групп 11. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- Репозиторий — 25
- Возможности SDK — 24
- Транзакции — 23
- Лог отклонений rules-log — 21
- Unit-тесты — 20
- Слои — 20
- Подтверждение коммита и пуша — 19
- Исключения и ошибки — 18
- Workflow — 18
- Внешние интеграции — 18
- Enum — 18
- Карта связей — 17
- Swagger — 17
- имена — 17
- Субагенты — 16
- Рефакторинг легаси — 16
- DI-контейнер — 16
- междоменные связи — 16
- Безопасность — 15
- RabbitMQ — 15

## Кандидаты в противоречия (conflicts_with)
C1. [agents/df-review.md § п.2] ««Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)» VS [approaches/process/planning.md § Волны] «Ревью ⇄ исправления — до 3 кругов» (0.15)
C2. [approaches/patterns/cache.md § начало] «Инвалидация — при записи в том же сценарии» VS [approaches/patterns/transactions.md § Общее] «Внутри транзакции нет HTTP, публикаций, файлов, dispatch, инвалидации кэша — после коммита» (0.25)
C3. [architecture/layers.md § Общий бизнес-словарь] «Common — только enum, value-объекты, чистые функции; зависимости — язык, библиотека дат, трейт enum ядра; копия enum — [ОШИБКА] duplicated-enum» VS [stacks/go/structure.md § Слои и зависимости] «Common → только стандартная библиотека и сам Common» (0.6)
C4. [architecture/layers.md § Общий бизнес-словарь] «Common — только enum, value-объекты, чистые функции; зависимости — язык, библиотека дат, трейт enum ядра; копия enum — [ОШИБКА] duplicated-enum» VS [approaches/process/testing.md § Правила] «Архитектурный тест границ Common: Go — падает на всём, кроме stdlib и internal/common/; PHP — на Illuminate\ кроме трейта enum; ставится в волну 0» (0.6)
C5. [core/style.md § Имена] «Аббревиатуры целиком заглавными: ID, URL, HTTP, DTO, JSON» VS [stacks/go/go-sdk/entrypoint/http.md § request.go / mapper.go] «Примеры request/mapper используют поле ParkingId (не ParkingID)» (0.7)
C6. [core/style.md § Имена] «Аббревиатуры целиком заглавными: ID, URL, HTTP, DTO, JSON» VS [stacks/go/go-sdk/workflow.md § dto.go] «Пример dto.go использует ParkingId (не ParkingID)» (0.7)
C7. [core/style.md § Имена] «Аббревиатуры целиком заглавными: ID, URL, HTTP, DTO, JSON» VS [stacks/go/go-sdk/domain.md § repository.go / service.go] «Примеры используют GetById/ParkingId (не GetByID)» (0.7)
C8. [core/style.md § Форма кода] «Мелкие шаги workflow допустимо повторить в соседнем workflow, не выносить в общий пакет» VS [stacks/go/conventions.md § Предупреждения] «Дублирование скелета обработчика — предупреждение, вынести в State Machine/Strategy» (0.3)
C9. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § --mr п.7] «База MR — Target Branch этого MR (фичи идут в stage), не дефолт» (0.15)
C10. [regulations/technical-approval.md § Требует согласования] «Требует согласования любое изменение схемы БД — таблицы, поля, типы, FK, ограничения» VS [core/principles.md § Действия с БД] «Массовые операции, очистка, схема/миграции — только по явному «да» каждый раз, касается и локальной БД; на stage/prod — не выполнять вообще» (0.1)
C11. [approaches/process/role-chain.md § Новые вопросы по ходу] «Новые вопросы по ходу: политика (а) по рекомендации с записью в rules-log — по умолчанию, (б) отложить кусок; согласование/внешний контракт — всегда отложить, действие с БД — стоп до «да»» VS [skills/dev/SKILL.md § Фаза 3] «Всё про правила — в rules-log.md; правила не правятся до разбора в Фазе 8» (0.2)
C12. [skills/dev/SKILL.md § Фаза 4] «Одновременно не больше 3 агентов (refactor/migrate — до 5) в одном рабочем дереве, без isolation/worktree» VS [approaches/process/role-chain.md § Авто-режим, Фаза 4] «Разработка: до 3 агентов параллельно, refactor/migrate — до 5 при нарезке без пересечений файлов» (0.25)
C13. [stacks/go/conventions.md § п. 12] «Интерфейс — в файле по имени интерфейса; ports.go — [ОШИБКА]» VS [stacks/go/conventions.md § п. 13] «Файл по абстракции (types.go, const.go, utils.go, ports.go) — [ВНИМАНИЕ]; enum — {name}_enum.go» (0.75)
C14. [stacks/go/review-findings.md § Структура и стиль] «Хелперы шагов дублируются в каждый workflow; преобразования формата — всё равно один хелпер» VS [stacks/php/checklist.md § п.46] «п.46 [В] Преобразование формата — один раз: mps/utils → Core/Helpers/{Thing}Helper; вызов в prepareForValidation/Response, не сервис» (0.2)
C15. [stacks/php/mps-core/rules.md § QueryFilters] «QueryFilters — только V2 (V1 нельзя)» VS [stacks/php/conventions.md § п. 2a] «Список — paginate{Что}({E}FilterDto, Page); search(), filterSearch(), SearchDataObject, PaginatedQueryHelper в новом коде не используются (P-19)» (0.3)
C16. [stacks/php/mps-core/service.md § Сложный сервис] «CrudServiceDecorator есть с mps/core 1.7.0; при старом ядре (superapp-api 1.6.0) — extends Service, свой интерфейс, CRUD через репозиторий» VS [stacks/php/structure.md § Дерево подпроекта] «TransactionManagerInterface/NoopTransactionManager в Core/ — только при mps/core < 1.10; с 1.10 — из ядра, своих нет» (0.2)
C17. [stacks/php/review-findings.md § Controller] «Метод-обёртка над одной проверкой (findByIdOrFail, assertValid) — service-thin-wrapper [ВНИМАНИЕ]» VS [stacks/php/review-findings.md § Repository] «Метод репозитория — один запрос (тег repository-multi-query, [ОШИБКА])» (0.15)
