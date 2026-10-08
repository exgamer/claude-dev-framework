# Журнал решений

Почему правила такие, какие есть. Перед тем как спорить с правилом — посмотреть сюда.
Открытые споры = обязательное обсуждение с пользователем, если задача их задевает (см. `core/principles.md`). Пока спор открыт, в новом коде — как в эталоне фреймворка (`stacks/<stack>/examples/`; он написан по основным эталонам: Go — `parking-session-service`, PHP — `parking_app`), и это явно проговаривается пользователю.

Префиксы: `O-` общие, `G-` Go, `P-` PHP.

---

## Открытые

| # | Вопрос | Варианты | Сейчас по факту |
|---|---|---|---|

## Принятые

| Дата | Решение | Где записано |
|---|---|---|
| 2026-10-07 | Папка точек входа в Go — `internal/entrypoints/` (мн. число), как в регламенте и parking; `entrypoint/` из шаблона — устаревшее | `stacks/go/structure.md` |
| 2026-10-07 | Транзакции в Go — через `gosdk-db-core/pkg/transaction` (`Manager*`), интерфейс `TxManager` объявляет потребитель | `stacks/go/go-sdk/infra/postgres.md` |
| 2026-10-07 | Спорное и не описанное — не решать молча: обсудить → зафиксировать в правилах → писать код | `core/principles.md`, `core/rules-evolution.md` |
| 2026-10-07 | Фронт на старте — vanilla JS (без фреймворка); *заменено ниже: фронт отложен (O-5)* | `stacks/vanilla-js/` |
| 2026-10-07 | Файл workflow — `{действие}_workflow.go`, тест `{действие}_workflow_test.go` (как в регламенте; parking — старый вариант) | `stacks/go/go-sdk/workflow.md`, `stacks/go/structure.md` |
| 2026-10-07 | Workflow собирается в `bootstrap/{module}/workflows_factory.go`, не в `services_factory.go` | `stacks/go/structure.md`, `stacks/go/review-findings.md` |
| 2026-10-07 | PHP-стек — Laravel + mps/core + mps/utils; эталоны `superapp-api/parking_app` (основной) и `super_app`; легаси `app/` — не эталон | `stacks/php/` |
| 2026-10-07 | «Сделано в проекте, но нет в регламенте» = тема для обсуждения, не образец | `core/principles.md` |
| 2026-10-07 | Параллельная работа: волны по `plan.md`, не больше 3 разработчиков одновременно, общие файлы регистрации — в последовательной волне | `approaches/process/planning.md` |
| 2026-10-07 | G-1: в слое workflow Go файлы только `*_workflow.go`; `*_command.go` там — либо междоменный (→ `_workflow`), либо однодоменный (→ Command домена) | `stacks/go/go-sdk/workflow.md`, `stacks/go/review-findings.md` |
| 2026-10-07 | P-4: в PHP интерфейс репозитория может возвращать Eloquent-модель из Infrastructure (единственное исключение Domain → Infrastructure, только PHP) | `architecture/layers.md`, `stacks/php/conventions.md` |
| 2026-10-07 | P-3/P-6: в PHP без `src/` — `Domains/{D}/Modules/{M}`, `Entrypoints/{Ctx}/{D}/{M}` | `stacks/php/structure.md` |
| 2026-10-07 | P-5/P-7: PHP-имена как в parking_app — `{Entity}Dto`, `Http/Requests/{Entity}/CreateRequest` | `stacks/php/style.md` |
| 2026-10-07 | Service / Command·Query / Workflow — разные вещи: Command/Query = сервис одного домена, вынесенный в класс из-за размера; Workflow = междоменная агрегатная логика (P-1) | `architecture/layers.md`, `stacks/php/*`, `stacks/go/go-sdk/domain.md` |
| 2026-10-07 | G-2/G-3: инфраструктура — своё хранилище по `{tech}/{domain}/{module}`, внешний провайдер по `http/{provider}/{product}`, наш сервис по `http/{service}/{module}`, прочие технологии (`jwt`) — `{tech}/{domain}/{module}` | `stacks/go/go-sdk/infra.md`, `stacks/go/structure.md`, `architecture/integrations.md`, `stacks/php/structure.md` |
| 2026-10-07 | O-6: Command/Query на несколько модулей одного домена — в модуле-владельце результата, остальные модули через интерфейсы | `architecture/layers.md`, `stacks/go/go-sdk/domain.md` |
| 2026-10-07 | P-2: междоменный класс в PHP — `{Action}Workflow`, метод `execute()`; `*Command` в `Workflows/` parking_app — старый нейминг, переименовывать только в рамках задачи | `stacks/php/structure.md`, `stacks/php/style.md` |
| 2026-10-07 | P-9: слой `Contexts/` из регламента проекта реализуется как `Entrypoints/{Context}/`, отдельной папки `Contexts/` нет | `stacks/php/structure.md` |
| 2026-10-07 | P-13: логика, отличающаяся по контексту (Admin/Mobile), — отдельный `{Context}{Entity}Service` (+ интерфейс, при необходимости репозиторий) в том же модуле домена; не в Entrypoints, без подпапок контекста | `stacks/php/conventions.md`, `stacks/php/structure.md` |
| 2026-10-07 | O-3: тесты — Go: unit рядом (`_test.go`, `testdata/`), интеграционные с тегом `integration`, e2e в `tests/`; PHP: только `tests/Unit|Feature/{Подпроект}/…` с путём как у класса | `stacks/go/structure.md`, `stacks/php/structure.md` |
| 2026-10-07 | O-4: источник и версию Go SDK всегда спрашивать у пользователя (раз на задачу, ответ — в `design.md`), `go.mod` — только контекст | `stacks/go/conventions.md` п. 23, `skills/dev-go`, `skills/dev-architect` |
| 2026-10-07 | O-4 (PHP): ядро `mps/core` + `mps/utils` одно — версию не спрашивать, брать из `composer.lock`, API сверять с `vendor/` | `stacks/php/conventions.md` |
| 2026-10-07 | G-4: служебные эндпойнты — контекст `system` с транспортом: `entrypoints/system/http/{name}` | `stacks/go/structure.md` |
| 2026-10-07 | P-8: доменная валидация PHP — `{E}DtoValidator`, inline-проверки — [ВНИМАНИЕ] | `stacks/php/conventions.md` |
| 2026-10-07 | P-10: PHP Infrastructure всегда с типом хранилища/транспорта в пути | `stacks/php/structure.md` |
| 2026-10-07 | P-11: `Core/` — только техническое общее, `Domains/Shared` не заводится | `stacks/php/structure.md` |
| 2026-10-07 | P-12: только готовые классы исключений ядра; `AppException` + enum — [ВНИМАНИЕ] | `stacks/php/conventions.md` |
| 2026-10-07 | O-5: фронт (vanilla JS, `/dev-front`) отложен до появления проекта-образца | `README.md` |
| 2026-10-07 | Старые скиллы `php-arch`, `php-reviewer`, `go-reviewer` пока остаются рядом с новыми (на них завязан `auto-review`) | — |
| 2026-10-07 | Эталонные модули: `stacks/go/examples/tariff` (собран и протестирован против SDK из go.mod parking), `stacks/php/examples` (не запускался — нет PHP/Docker) | `stacks/*/examples/README.md`, скиллы `/dev-go`, `/dev-php`, `/dev-review` |
| 2026-10-07 | Фреймворк знает весь API SDK и ядра: `capabilities.md` (задача → API) + `reference/` (генерируется из исходников `tools/sdk-ref/`, по версиям); переизобретение и вызов несуществующего API — [ОШИБКА] | `stacks/go/go-sdk/`, `stacks/php/mps-core/`, `core/principles.md` |
| 2026-10-07 | Перенос ревьюеров `claude-skills`: чеклисты безопасности с тегами, проверки за пределами дифа, MR через glab, формат отчёта для `auto-review`, режим `--security-audit` (позже перенесён в `/dev-security audit`) | `approaches/process/review.md`, `stacks/*/security.md`, `stacks/*/review-checks.md`, `stacks/php/review-tags.md` |
| 2026-10-07 | PHP: без Eloquent-связей в моделях; клиент внешней системы — `*Gateway` (так в эталонах и в `php-reviewer`) | `stacks/php/conventions.md` |
| 2026-10-07 | Источник истины фреймворка — `~/AIProjects/dev-framework`; `~/.claude` и копии в проектах — производные, обновляются из него (`install.sh`) | `core/rules-evolution.md`, `README.md`, `skills/dev-rule` |
| 2026-10-07 | O-7: workflow — агрегат, берёт репозитории/сервисы любых доменов напрямую; Command/Query/Service лежат в домене — чужой домен им недоступен (иначе → workflow) | `architecture/layers.md`, `architecture/cross-domain.md`, `stacks/go/go-sdk/domain.md`, `stacks/php/conventions.md`, `stacks/php/review-tags.md` |
| 2026-10-07 | O-9: агент не выполняет массовые операции в БД, очистку (`TRUNCATE`/`DROP`/`migrate:fresh`…), создание и изменение схемы (в т.ч. миграции) без явного «да» на каждую; на stage/prod — никогда, только предлагает команду; авто-режим `/dev` не отменяет | `core/principles.md`, `approaches/process/{self-check,role-chain}.md`, скиллы dev/go/php/qa |
| 2026-10-07 | O-8: workflow (агрегат) транзакционен по умолчанию — проверки и все записи в одной транзакции; без неё — только с причиной в «Границах транзакций» `design.md` (только чтение, внешний вызов → outbox/сага, пачки). Go: внутри `txManager.Exec` только репозитории из аргументов — сервис домена пишет мимо транзакции | `approaches/patterns/transactions.md`, `architecture/*`, `stacks/go/{conventions,review-findings}.md`, `go-sdk/{workflow,infra/postgres}.md`, `stacks/php/{conventions,review-checks,review-tags}.md`, `mps-core/transactions.md`, `approaches/process/review.md`, скиллы architect/go/php, эталоны |
| 2026-10-07 | P-16: кейсы enum в PHP — `UPPER_SNAKE` (как ядро и `super_app`); PascalCase в части `parking_app` — старый стиль, переименовывать только в рамках задачи | `stacks/php/style.md`, `stacks/php/examples/` |
| 2026-10-07 | Роль `/dev-security`: `design` — модель угроз до кода для задач с чувствительными зонами (`threats.md`), `audit` — глубокий аудит (перенесён из `/dev-review --security-audit`, тот остался синонимом); чеклисты общие — `stacks/*/security.md` | `approaches/process/security.md`, `skills/dev-security`, цепочка `/dev` |
| 2026-10-07 | `/dev` по умолчанию — авто-режим: разведка → один список вопросов (чеклист «Что выяснить до старта») → результат без остановок; ревью-исправления до 3 кругов; новые вопросы по ходу — по политике из начала (по умолчанию — рекомендация + запись в правила); `--step` — пошагово; коммит — только по «да» | `skills/dev`, `approaches/process/role-chain.md`, `core/principles.md` |
| 2026-10-07 | Рефактор и перенос стека: «сохраняем ли контракты старого проекта» — всегда вопрос пользователю (инвентаризация HTTP/событий/данных/консоли + варианты 1:1 / новая версия рядом / менять свободно), не решается по умолчанию | `approaches/process/legacy-refactor.md`, `stack-migration.md`, `role-chain.md`, `skills/dev`, `skills/dev-architect` |
| 2026-10-07 | G-5: константы-перечисления в Go — `{name}_enum.go` (как в `go-sdk/domain.md`); `const.go` из п. 13 `conventions.md` — устаревшее, п. 13 приведён к `domain.md` | `stacks/go/conventions.md` п. 13, `stacks/go/go-sdk/domain.md` |
| 2026-10-07 | Лог отклонений по задаче `docs/tasks/<ключ>/rules-log.md`: все роли пишут споры, пробелы, отступления, решения по ходу, расхождения проекта, правки стиля; правила правятся только после разбора в конце задачи (во фреймворк / в проект / оставить) | `core/rules-evolution.md`, `core/principles.md`, `approaches/process/role-chain.md`, все скиллы |
| 2026-10-07 | Карта связей обязательна для любой задачи, задевающей существующий код (рефактор, доработка, перенос): входящие/исходящие/неявные связи, другие репозитории; строит архитектор, разработчик сверяется, ревью и QA проверяют | `approaches/process/impact-analysis.md`, роли |
| 2026-10-07 | Роль `/dev-analyst` — первый шаг `/dev`: задача → `requirements.md` (участники, сценарии, правила R*, критерии приёмки AC*, границы, неясности); архитектор проектирует по требованиям, QA пишет кейсы по AC* | `approaches/process/analysis.md`, `skills/dev-analyst`, цепочка `/dev` |
| 2026-10-07 | Процесс по лучшим практикам: тест-кейсы до кода (`/dev-qa cases`), unit-тесты пишет разработчик вместе с кодом, интеграционные/e2e — QA до ревью, независимое ревью отдельным субагентом (код + тесты), Definition of Done, рабочая ветка по регламенту до кода, статусы задач в `plan.md`, раздел «Выкатка» в дизайне | `skills/dev`, `approaches/process/role-chain.md`, `planning.md`, `self-check.md`, `review.md`, роли |
| 2026-10-07 | Требования к производительности: горячие пути и объёмы — в требованиях, бюджеты и индексы — в дизайне; правила БД/кэша/внешних вызовов/памяти с тегами; проверяют разработчик, ревью, QA; входят в Definition of Done | `approaches/patterns/performance.md`, роли |
| 2026-10-07 | Конкурентность при необходимости: архитектор отмечает места одновременности в «Конкурентность» `design.md`; race condition — ограничения БД, условные апдейты, идемпотентность, блокировки крона/воркеров; data race (Go) — mutex/atomic/каналы, `go test -race`; QA — тесты с параллельными запросами | `approaches/patterns/concurrency.md`, роли |
| 2026-10-08 | O-10: PUT получает полные валидные данные — каждое поле обязательно к передаче (`required` или `present\|nullable`), неприсланное — 422; сервис пишет DTO целиком. Механизмы частичного обновления (список полей рядом с DTO, отслеживание пришедших полей в DTO, отбрасывание `null`) не применяются. Повод — AINA-2628: `BuildingService`, `AgreementTransactionService` затирали неприсланные поля `null` при `nullable` в Request; эталон `TariffCrudService` приведён к `present\|nullable` | `regulations/rest-api.md`, `stacks/php/{conventions,review-findings,review-tags}.md`, `stacks/php/examples/` |
| 2026-10-08 | Doc-комментарий обязателен, только если говорит больше имени и сигнатуры; описание-пересказ имени — [ИНФО], тег PHP `comment-restates-name` (повод — nit ревью AINA-2628) | `core/style.md`, `stacks/php/{style,review-findings,review-tags}.md`, `stacks/go/style.md` |
| 2026-10-08 | Обход поведения ядра/SDK — только после проверки (исходник или прогон), иначе [ВНИМАНИЕ]; PHP: тип сеттера DTO = тип свойства, строки из multipart `fromArray()` приводит сам, тег `dto-setter-widened-type` (повод — blocking ревью AINA-2628) | `core/principles.md`, `stacks/php/mps-core/data-objects.md`, `stacks/php/{review-findings,review-tags}.md` |
