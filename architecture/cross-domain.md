# Связи между доменами

---

## Выбор подхода

| Ситуация | Подход |
|---|---|
| Сервису A нужно прочитать данные домена B | прямая зависимость через **интерфейс, объявленный у потребителя (A)** |
| Проверка существования/валидность по данным домена B | то же; либо workflow, если проверка — часть сценария записи |
| Операция пишет в 2+ домена | **workflow** |
| Многошаговый процесс (запись → событие → запись в другом домене) | workflow + outbox (см. `approaches/patterns/outbox.md`) |
| Одна и та же операция вызывается из HTTP и consumer | workflow |

## Правила

- Интерфейс — у потребителя, узкий (только нужные методы). Провайдер о нём не знает.
- Циклов между доменами нет. Если A нужен B и B нужен A — общая часть уходит в workflow.
- Workflow получает репозитории и сервисы доменов через конструктор — **напрямую, без посредничества сервисов** (это агрегат, O-7). К БД, кэшу, HTTP — только через репозитории доменов, без `gorm`/`DB::`/клиентов в самом workflow.
- Command/Query домена репозиторий чужого домена не берёт — у него только свой домен; понадобился чужой → это workflow.
- Workflow транзакционен по умолчанию — `approaches/patterns/transactions.md` (O-8).
- Домен не импортирует чужие DTO/сущности для записи — workflow собирает данные и передаёт каждому домену его DTO.

## Пример (одинаковый смысл в двух стеках)

Создание тарифа требует существующей парковки (Catalog) — Billing о Catalog не знает:

- Go: `stacks/go/examples/tariff/internal/workflows/billing/tariff/create_tariff_workflow.go` → `Exec(ctx, params)`: в одном `txManager.Exec` — `parkingRepository.GetByID` → `tariffRepository.Create`.
- PHP: `stacks/php/examples/parking_app/Workflows/Billing/Tariffs/CreateTariffWorkflow.php` → `execute(TariffDto)`: в одном `transactionManager->run()` — `parkingRepository->oneById` → `tariffCrudService->create` (в `parking_app` тот же класс называется `CreateTariffCommand` — старый нейминг, P-2).
