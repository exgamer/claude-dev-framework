# Связи между доменами

---

## Выбор подхода

| Ситуация | Подход |
|---|---|
| Сервису A нужны данные домена B | **workflow**: берёт репозитории/сервисы B напрямую и передаёт данные сервису A его DTO/параметрами (O-7) |
| Проверка существования/валидность по данным домена B | **workflow** (как `CreateTariffWorkflow`: парковка проверяется до создания тарифа) |
| Операция пишет в 2+ домена | **workflow** |
| Многошаговый процесс (запись → событие → запись в другом домене) | workflow + outbox (см. `approaches/patterns/outbox.md`) |
| Одна и та же операция вызывается из HTTP и consumer | workflow |

## Правила

- Домен (Service, Command/Query) не зависит от чужого домена ни через репозиторий, ни через сервис, ни через «свой» интерфейс на чужой сервис — **[ОШИБКА]** (O-7). Интерфейс у потребителя остаётся только для адаптера внешней системы или легаси вне доменов (`integrations.md`): домен объявляет порт, Infrastructure его реализует.
- Циклов между доменами нет. Если A нужен B и B нужен A — общая часть уходит в workflow.
- Workflow получает репозитории и сервисы доменов через конструктор — **напрямую, без посредничества сервисов** (это агрегат, O-7). К БД, кэшу, HTTP — только через репозитории доменов, без `gorm`/`DB::`/клиентов в самом workflow.
- Workflow транзакционен по умолчанию — `approaches/patterns/transactions.md` (O-8).
- Домен не импортирует чужие DTO/сущности для записи — workflow собирает данные и передаёт каждому домену его DTO.
- **Read-only подзапрос к таблице чужого домена** (`EXISTS`/`JOIN` для фильтра или выборки) в своём репозитории допустим — с комментарием, чья таблица: это условие выборки своего домена на уровне SQL; поля чужой таблицы в результат не отдаются, модели и репозитории чужого домена не берутся. Нужны сами данные домена B, запись в его таблицы или его бизнес-правило (существование как правило, статусы) — workflow через репозитории/сервисы B (AINA-2978).
  ```
  // хорошо: OrganizationRepository — организация, где пользователь числится сотрудником
  ->whereExists(fn ($q) => $q->selectRaw('1')->from('employees')   // таблица домена User, только чтение
      ->whereColumn('employees.organization_id', 'organizations.id')
      ->where('employees.user_id', $userId)->whereNull('employees.deleted_at'))
  // плохо: OrganizationRepository берёт EmployeeRepository или пишет в employees
  ```

## Пример (одинаковый смысл в двух стеках)

Создание тарифа требует существующей парковки (Catalog) — Billing о Catalog не знает:

- Go: `stacks/go/examples/tariff/internal/workflows/billing/tariff/create_tariff_workflow.go` → `Exec(ctx, params)`: в одном `txManager.Exec` — `parkingRepository.GetByID` → `tariffRepository.Create`.
- PHP: `stacks/php/examples/parking_app/Workflows/Billing/Tariffs/CreateTariffWorkflow.php` → `execute(TariffDto)`: в одном `transactionManager->run()` — `parkingRepository->oneById` → `tariffCrudService->create` (в `parking_app` тот же класс называется `CreateTariffCommand` — старый нейминг, P-2).
