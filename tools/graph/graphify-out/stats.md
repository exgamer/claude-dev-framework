# Граф правил — сводка

Узлов 1116, связей 4259, групп 13. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- skills/dev-mr/SKILL.md
- stacks/go/go-sdk/entrypoint.md
- stacks/go/go-sdk/infra/redis.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 28
- Транзакции — 24
- DI-контейнер — 22
- Внешние интеграции — 20
- Исключения и ошибки — 19
- Конкурентность — 19
- Лог отклонений rules-log — 19
- RabbitMQ — 18
- Подтверждение коммита и пуша — 18
- Unit-тесты — 18
- Слои — 17
- миграции БД — 17
- Swagger — 17
- возможности SDK — 17
- Субагенты — 16
- Ревью MR — 16
- Сервисный слой — 16
- Авторизация и права — 16
- enum — 16
- Хелперы и переиспользование — 16

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/process/security.md § Режим audit C] «Аудит: 4–8 параллельных агентов Explore без isolation, только чтение, запущенных одним сообщением» VS [approaches/process/security.md § финал] «Агентов-разработчиков не больше 3 (в рефакторе 5) по planning.md, против 4–8 у аудита» (0.2)
C2. [architecture/layers.md § Принадлежность записи скоупу] «Внутренние точки входа читают без ограничения: скоуп передаётся явно — nil (Go) / null (PHP); null у super-admin» VS [stacks/go/conventions.md § п. 7a] «п. 7a: скоуп ParkingScope{All;IDs}: «без ограничения» — явный All: true (super-admin, consumer, консоль, integration); [ОШИБКА] scope-param-defaults-unrestricted» (0.75)
C3. [regulations/git.md § Ветки] «Рабочая ветка от release; hotfix — от production; поток ветка → MR → stage → release → production» VS [approaches/process/review.md § --mr п. 7] «База ревью MR — Target Branch MR, не дефолт (фичи в stage)» (0.15)
C4. [stacks/go/conventions.md § п. 6] «п. 6: вся бизнес-логика — только в сервисах и workflow» VS [stacks/go/checklist.md § п. 14] «п. 14: >1 модифицирующего запроса — один txManager.Exec; один — без транзакции (needless-transaction)» (0.2)
C5. [stacks/go/go-sdk/infra/postgres.md § Правила] «Каждый DB-запрос — timeout 10s (кроме NewGormPaginatedHelper)» VS [stacks/go/go-sdk/infra/http.md § Конфигурация] «SetRequestTimeout на каждый запрос (default 30s)» (0.1)
C6. [stacks/go/go-sdk/workflow.md § Именование файлов] «Собирается в internal/app/bootstrap/{module}/workflows_factory.go, не services_factory.go» VS [stacks/go/examples/tariff/README.md § шапка] «Проверено 2026-10-20 против gosdk-core v1.0.9, gosdk-db-core v1.0.10, http-request-builder v1.0.2 и др. (Go 1.26+)» (0.15)
C7. [stacks/php/conventions.md § п. 11b] «Элемент внешнего массива (в т.ч. keyBy()-карта из репозитория) — только после проверки ключа ([ОШИБКА] array-key-unchecked)» VS [stacks/php/review-findings.md § Repository] «Репозиторий: нет keyBy/map/сборки структур — маппинг в Service» (0.3)
C8. [stacks/php/mps-core/cli.md § Соглашения] «Signature: {domain}:{module}:{action} через двоеточие» VS [stacks/go/structure.md § Консоль] «Консольная команда: Use "{module-kebab}:{action}", тонкая — один вызов Command домена» (0.2)
C9. [stacks/php/mps-core/migrations.md § «ALTER TABLE с заполнением»] «Data-миграция пишет через типизированный метод репозитория (один UPDATE … WHERE, P-20); зависимость — app(Interface::class)» VS [architecture/layers.md § Правила слоёв] «DI — только в Bootstrap; resolve в бизнес-коде — [ОШИБКА]; PHP ServiceInterface всегда, Go — по необходимости» (0.25)
C10. [stacks/php/mps-core/transactions.md § По умолчанию] «CRUDService сам оборачивает create/update/delete в транзакцию через getRepository()->transaction()» VS [stacks/php/mps-core/transactions.md § Как использовать] «$this->repository->transaction() / $service->transaction() — антипаттерн на уровне Service/Command» (0.2)
C11. [stacks/php/mps-core/transactions.md § Как использовать] «DB::transaction() — только в реализации менеджера транзакций (Infrastructure), при PostgreSQL-специфике» VS [stacks/php/checklist.md § п. 20] «Транзакция — только run(); не DB::transaction, не $repo->transaction» (0.15)
C12. [stacks/php/review-checks.md § 5.2] «Бизнес-исключение в Repository, кроме NotFoundAppException, — [ОШИБКА]» VS [stacks/php/mps-core/repository.md § Контракт репозитория] «Репозиторий не бросает бизнес-исключения и «не найдено» — возвращает null; не делает keyBy» (0.7)
C13. [stacks/php/review-checks.md § 5.2] «Бизнес-исключение в Repository, кроме NotFoundAppException, — [ОШИБКА]» VS [stacks/php/mps-core/repository.md § ЗАПРЕЩЕНО] «Своих *OrFail в репозитории нет; get…/oneBy… → ?Model, NotFoundAppException бросает вызывающий» (0.65)
C14. [stacks/php/review-checks.md § 5.2] «$cache->getOrCreate(...) вместо SmartCache/getOrSet — [ОШИБКА] (с поправкой по версии utils)» VS [stacks/php/conventions.md § п. 22] «getOrCreate устаревший только с utils ≥ 2.6.4; при utils < 2.6.4 — не находка» (0.6)
C15. [stacks/php/review-checks.md § 5.2] «$cache->getOrCreate(...) вместо SmartCache/getOrSet — [ОШИБКА] (с поправкой по версии utils)» VS [stacks/php/mps-core/capabilities.md § Кеш и Redis] «utils ≥ 2.6.4: getOrSet вместо deprecated getOrCreate; SmartCache — utils ≥ 2.8» (0.3)
C16. [stacks/php/review-checks.md § 5.5 п. 3] «Тесты в проекте локально не запускаются (битый RefreshDatabase) — сказать об этом» VS [stacks/php/checklist.md § п. 39] «Прогнаны csfix-validate, phpstan, test:run; вывод показан; не запускал — так и написано» (0.25)
