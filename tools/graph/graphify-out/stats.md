# Граф правил — сводка

Узлов 1092, связей 4373, групп 12. Кандидаты ниже — подсказки, проверять по файлам.

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
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 30
- транзакции — 26
- теги ревью — 24
- возможности SDK/ядра — 22
- Ошибки и исключения — 22
- Конкурентность — 21
- безопасность — 19
- RabbitMQ — 19
- Авторизация и права — 19
- субагенты — 18
- Подтверждение коммита/пуша — 18
- Swagger — 18
- unit-тесты — 17
- HTTP точка входа — 16
- миграции БД — 16
- enum — 16
- хелперы и переиспользование — 16
- оркестратор /dev — 15
- Внешние интеграции — 15
- производительность — 15

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [stacks/go/checklist.md § п. 10] «4+ аргумента у функции (не конструктора; ctx не в счёт) → Params в dto.go» VS [core/backlog.md § B-80] «B-80: в счёт «4+ аргумента» не считать t *testing.T, b, f, ctx context.Context (сейчас считаются по core/style.md)» (0.25)
C3. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § Цель ревью, --mr п. 5, 7] «База ревью MR — Target Branch этого MR; без glab и без названной цели — stage» (0.15)
C4. [stacks/go/checklist.md § п. 13] «Не-бизнесовая проблема: своя реализация при готовом компоненте — О; третья копия — В вынести в pkg/; вторая копия — И кандидат на вынос» VS [stacks/go/conventions.md § п. 22] «Одинаковый инфраструктурный workaround в двух файлах — infra-logic-duplicated, даже если обе копии работают; вынести в pkg/» (0.65)
C5. [stacks/go/checklist.md § п. 36] «Doc-комментарий на публичных типах и полях сущностей — только если добавляет смысл к имени; формат «Имя Описание по-русски»» VS [stacks/go/conventions.md § Предупреждения] «Предупреждение: у entity.go/model.go нет doc-комментария на структуре или у неочевидного поля» (0.3)
C6. [architecture/layers.md § Метод репозитория — один запрос] «Публичный метод репозитория — ровно один запрос (SELECT/UPDATE/DELETE/INSERT/upsert); count+страница пагинатора считается одним» VS [stacks/go/conventions.md § п. 15b] «Метод репозитория — одна завершающая операция (First/Find/Count/Pluck/Updates/Delete/Create/Exec); обновление — Updates + RowsAffected» (0.2)
C7. [stacks/go/checklist.md § п. 13] «Не-бизнесовая проблема: своя реализация при готовом компоненте — О; третья копия — В вынести в pkg/; вторая копия — И кандидат на вынос» VS [stacks/go/go-sdk/entrypoint/rabbit.md § Кастомный Marshaler] «Кастомный Marshaler — один (DeathTolerantMarshaler SDK); две реализации — infra-logic-duplicated, заменить обе на общую в pkg/» (0.6)
C8. [stacks/go/go-sdk/infra/postgres.md § Пагинация] «per_page не передан → умолчание хелпера 30; SetPerPage только при PerPage > 0 (иначе SDK подставит 20)» VS [stacks/php/review-findings.md § Controller] «per_page в пагинации задаётся явно, не «по умолчанию где-то в ядре»» (0.25)
C9. [stacks/go/structure.md § Entrypoint Layer, Консоль] «Консоль — свой bootstrap internal/console/bootstrap/{domain}/{module}/; серверный bootstrap в консоль не подключается» VS [stacks/go/structure.md § Bootstrap / Application Layer] «Одна настройка — одно место: env-переменную читает config.go владельца, остальные — через Load{Name}() (консоль импортирует серверный пакет ради LoadMaxAttempts)» (0.3)
C10. [architecture/layers.md § Общий бизнес-словарь] «Common — только enum, value-объекты, чистые функции; без зависимостей кроме языка, дат и трейта enum ядра; копия понятия — duplicated-enum, логика — common-with-logic» VS [stacks/go/structure.md § Слои и зависимости] «internal/common → только стандартная библиотека и сам common; тест границ boundary_test.go» (0.25)
C11. [architecture/layers.md § Роли и права на операцию] «Роли/права на операцию — именованная middleware на маршруте; права по модулю {подпроект}.{context}.{module}, по действиям (.view/.create/.update) — только если чтение и запись разделены по ролям» VS [stacks/php/examples/README.md § Entrypoints/Admin/Billing] «Права — permission:parking.admin.tariffs.{view|create|update} на каждом маршруте (чтение и запись различаются, п. 19a)» (0.3)
C12. [stacks/go/go-sdk/entrypoint/rabbit.md § Правила] «Ошибка десериализации/пустое поле — errorreporter.CaptureError с ID сообщения и nil (ACK); молчаливый ACK без трекера — нет» VS [stacks/php/examples/README.md § Entrypoints/Queue] «Consumer handbook.parking.deactivated: нет parking_id — errorLog и return (постоянная); исключение Command — наверх (delayed 60/300/900/3600)» (0.3)
C13. [stacks/php/review-checks.md § Архитектурные нарушения] «Строка таблицы без тега — [ОШИБКА] временно, до заведения тега (O-24)» VS [stacks/php/review-tags.md § вводная] «Уровень находки — только в каталогах (O-24): новый код — уровень каталога, унаследованное — [ВНИМАНИЕ]» (0.3)
C14. [stacks/php/review-tags.md § таблица] «controller-auth-via-facade — [ОШИБКА]: Auth::id()/auth() в глубине сервиса вместо $request->user() в контроллере» VS [stacks/php/conventions.md § HTTP п.19a] «Права и контекст — в middleware, не в сервисе; hasRole/authorize/Gate в контроллере, сервисе, workflow — нет; чужая запись по id — 404» (0.2)
