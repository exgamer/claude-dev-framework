# Граф правил — сводка

Узлов 1205, связей 4949, групп 10. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- Транзакции — 28
- Репозиторий — 24
- Конкурентность — 23
- Возможности SDK/ядра — 22
- Ошибки и исключения — 21
- Подтверждение коммита и пуша — 20
- Лог отклонений rules-log — 19
- Безопасность — 19
- Авторизация и права — 19
- HTTP точка входа — 18
- Контракты — 18
- DI и регистрация — 18
- Swagger — 18
- Миграции БД — 17
- Unit-тесты — 17
- слои — 17
- Валидация — 17
- субагенты — 16
- Теги находок — 16
- Внешние интеграции — 16

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § вводная] «TTL — константа в кэш-репозитории (2*time.Hour); фоновых пулеров/горутин обновления нет» VS [approaches/patterns/concurrency.md § Race condition] «Порядок: ограничение БД → FOR UPDATE → оптимистическая версия → распределённая блокировка; mutex не защищает» (0.15)
C2. [approaches/process/security.md § Режим audit] «Аудит — 4–8 параллельных Explore-агентов только на чтение, не «не больше 3»» VS [approaches/process/planning.md § Команда] «Не больше 3 разработчиков одновременно (+1 QA, +1 ревьюер); в refactor/migrate — до 5» (0.15)
C3. [core/principles.md § Сходимость цикла] «Найденное вне правки: в бэклог фреймворка — только [БЕЗОПАСНОСТЬ] и [ОШИБКА], [ВНИМАНИЕ] — списком в отчёт» VS [core/backlog.md § шапка] «В бэклог: «было и до правки» [БЕЗОПАСНОСТЬ]/[ОШИБКА] из Шага 3а и [ОШИБКА]/[ВНИМАНИЕ] из полной сверки» (0.3)
C4. [core/backlog.md § D-3] «D-3: расход 46–120 млн токенов на задачу, оркестратор 22–36%; не делать: предел шагов ниже 120, лимит кругов ревью» VS [core/principles.md § Сходимость цикла] «Цикл «найти — исправить»: предел 3 круга, повторный — по дельте; [ИНФО] не повод для круга и не строка бэклога» (0.25)
C5. [core/backlog.md § B-134] «B-134: у меры threats.md теперь нет файла (только слой) — «меры по твоим файлам» устарело, нужно «меры threats: Th… своей задачи»» VS [skills/dev-go/SKILL.md § Шаг 2] «Меры threats.md по твоим файлам обязательны, как критерии готовности» (0.75)
C6. [core/backlog.md § B-134] «B-134: у меры threats.md теперь нет файла (только слой) — «меры по твоим файлам» устарело, нужно «меры threats: Th… своей задачи»» VS [skills/dev-php/SKILL.md § Шаг 2] «Меры threats.md по твоим файлам обязательны, как критерии готовности» (0.75)
C7. [core/backlog.md § B-191] «B-191: «пользователь нужен трижды» (README, dev-intro) против role-chain (ещё перезапуск и стоп на БД)» VS [approaches/process/role-chain.md § Авто-режим] «Пользователь нужен: ответить на вопросы, решить по логу отклонений, подтвердить коммит; ещё перезапуск сессии» (0.7)
C8. [core/backlog.md § D-9] «D-9: может ли workflow объявлять интерфейс на сервис домена для мока — workflow.md разрешает, contracts.md «в Go не мокаем»» VS [regulations/contracts.md § Моки в тестах] «Мокаем Repository, адаптеры, шину, Clock/UUID; бизнес-сервисы в Go не мокаем, в PHP — через ServiceInterface» (0.7)
C9. [regulations/contracts.md § Интерфейс не нужен] «Сервисы по умолчанию без интерфейса (Go п. 10); PHP — исключение: {E}ServiceInterface у каждого сервиса (п. 5)» VS [stacks/php/conventions.md § п. 5] «RepositoryInterface — всегда, ServiceInterface — всегда; лимит методов — ориентир, CRUD ядра не в счёт; Command/Query без интерфейса» (0.15)
C10. [regulations/entity.md § Создание, сборка из БД] «Пример: CanCancel() bool + Cancel() error» VS [stacks/go/examples/tariff/README.md § entity.go] «CanMakeDefault(): отключённый — ErrTariffInactive (проверка допустимости с ошибкой)» (0.3)
C11. [regulations/rest-api.md § URL] «URL /api/<context>/<version>/<domain>/<module>/<entity>/<action?> для нового кода (url-not-by-scheme), версия v1» VS [stacks/go/examples/tariff/README.md § healthcheck] «Путь /healthcheck/* вне схемы /api/<context>/<version>/… намеренно; сбой пробы — 503, logger.Warning без Sentry» (0.7)
C12. [stacks/go/checklist.md § п. 23] «go — только в workflow/entrypoint с lifecycle (unbounded-goroutines); после записи — outbox» VS [stacks/go/examples/tariff/README.md § infrastructure/rabbit/…/publisher.go] «RabbitPublisher: таймаут публикации 5 с в адаптере (SDK ctx не принимает); дубль отсекает получатель по event_id» (0.3)
C13. [stacks/go/conventions.md § Рекомендации] «Не использовать глобальные переменные» VS [stacks/go/examples/tariff/README.md § domains/identity/admin] «Sentinel-ошибки — в errors.go (var Err…)» (0.3)
C14. [stacks/php/checklist.md § п. 54] «4+ аргумента (не конструктор) → DTO; допустимо до 3 позиционных + DTO опций, у приватного шага до ~5» VS [stacks/php/examples/README.md § SignatureHelper] «sign/verify — не больше трёх аргументов (core/style.md)» (0.2)
C15. [stacks/php/mps-core/transactions.md § По умолчанию] «CRUDService автоматически оборачивает create()/update()/delete() в транзакцию — дополнительно не нужно» VS [stacks/php/mps-core/service.md § Простой сервис] «CRUDService — только для чтения справочника; запись — свои методы репозитория с DTO, не create()/update() ядра с массивом (write-array-instead-of-dto)» (0.3)
C16. [stacks/php/review-checks.md § Архитектурные нарушения] «Нарушение без тега — [ОШИБКА] временно (O-24)» VS [stacks/php/review-tags.md § вводная] «Уровень находки — только в каталогах (O-24): новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]» (0.25)
