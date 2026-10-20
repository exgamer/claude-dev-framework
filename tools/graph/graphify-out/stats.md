# Граф правил — сводка

Узлов 1147, связей 4500, групп 10. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- skills/dev-mr/SKILL.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- Репозиторий — 26
- Транзакции — 25
- Возможности SDK — 24
- Workflow — 24
- Слои — 23
- Unit-тесты — 22
- Лог отклонений rules-log — 22
- Enum — 20
- Swagger — 20
- Внешние интеграции — 19
- Подтверждение коммита и пуша — 19
- Исключения и ошибки — 18
- DI-контейнер — 18
- междоменные связи — 18
- RabbitMQ — 17
- DTO — 17
- Карта связей — 17
- Правила валидации — 17
- HTTP точка входа — 17
- именование — 17

## Кандидаты в противоречия (conflicts_with)
C1. [agents/df-review.md § п.2] ««Круг N» — только закрытие находок + перечисленные файлы (правило «Повторный круг»)» VS [approaches/process/planning.md § Волны] «Ревью ⇄ исправления — до 3 кругов» (0.15)
C2. [approaches/patterns/cache.md § начало] «Инвалидация — при записи в том же сценарии» VS [approaches/patterns/transactions.md § Общее] «Внутри транзакции нет HTTP, публикаций, файлов, dispatch, инвалидации кэша — после коммита» (0.25)
C3. [architecture/layers.md § Общий бизнес-словарь] «Common — только enum, value-объекты, чистые функции; зависимости — язык, библиотека дат, трейт enum ядра; копия enum — [ОШИБКА] duplicated-enum» VS [approaches/process/testing.md § Правила] «Архитектурный тест границ Common: Go — падает на всём, кроме stdlib и internal/common/; PHP — на Illuminate\ кроме трейта enum; ставится в волну 0» (0.6)
C4. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § --mr п.7] «База MR — Target Branch этого MR (фичи идут в stage), не дефолт» (0.15)
C5. [regulations/technical-approval.md § Требует согласования] «Требует согласования любое изменение схемы БД — таблицы, поля, типы, FK, ограничения» VS [core/principles.md § Действия с БД] «Массовые операции, очистка, схема/миграции — только по явному «да» каждый раз, касается и локальной БД; на stage/prod — не выполнять вообще» (0.1)
C6. [approaches/process/role-chain.md § Новые вопросы по ходу] «Новые вопросы по ходу: политика (а) по рекомендации с записью в rules-log — по умолчанию, (б) отложить кусок; согласование/внешний контракт — всегда отложить, действие с БД — стоп до «да»» VS [skills/dev/SKILL.md § Фаза 3] «Всё про правила — в rules-log.md; правила не правятся до разбора в Фазе 8» (0.2)
C7. [skills/dev/SKILL.md § Фаза 4] «Одновременно не больше 3 агентов (refactor/migrate — до 5) в одном рабочем дереве, без isolation/worktree» VS [approaches/process/role-chain.md § Авто-режим, Фаза 4] «Разработка: до 3 агентов параллельно, refactor/migrate — до 5 при нарезке без пересечений файлов» (0.25)
C8. [stacks/go/checklist.md § п. 22] «п.22: panic — только при старте; обязательной настройки нет — не стартуем, не дефолт» VS [stacks/go/conventions.md § п. 9] «п.9: panic() запрещён в production; допустим только при инициализации приложения» (0.15)
C9. [stacks/go/go-sdk/domain.md § Правила] «Все DTO домена в одном dto.go; разбивать только если файл ~100+ строк» VS [stacks/go/conventions.md § п. 13] «п.13: enum — {name}_enum.go, DTO — dto.go, сущности — entity.go; types.go/const.go/utils.go — [ВНИМАНИЕ]» (0.2)
C10. [stacks/go/review-findings.md § Структура и стиль] «Workflow самодостаточен: общие хелперы шагов дублируются в каждый workflow, не шарятся» VS [stacks/go/conventions.md § п. 22] «п.22 [ОШИБКА]: не-бизнесовая функциональность (транспорт, сериализация, ретраи) не реализуется дважды — вынести в pkg/ или SDK» (0.25)
C11. [stacks/php/mps-core/rules.md § QueryFilters] «QueryFilters — только V2 (V1 нельзя)» VS [stacks/php/conventions.md § п. 2a] «Список — paginate{Что}({E}FilterDto, Page); search(), filterSearch(), SearchDataObject, PaginatedQueryHelper в новом коде не используются (P-19)» (0.3)
C12. [stacks/php/mps-core/service.md § Сложный сервис] «CrudServiceDecorator есть с mps/core 1.7.0; при старом ядре (superapp-api 1.6.0) — extends Service, свой интерфейс, CRUD через репозиторий» VS [stacks/php/structure.md § Дерево подпроекта] «TransactionManagerInterface/NoopTransactionManager в Core/ — только при mps/core < 1.10; с 1.10 — из ядра, своих нет» (0.2)
C13. [stacks/php/review-findings.md § Controller] «Метод-обёртка над одной проверкой (findByIdOrFail, assertValid) — service-thin-wrapper [ВНИМАНИЕ]» VS [stacks/php/review-findings.md § Repository] «Метод репозитория — один запрос (тег repository-multi-query, [ОШИБКА])» (0.15)
