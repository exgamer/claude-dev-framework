# Domain слой

Путь: `internal/domains/{domain}/{module}/`

Примеры: `internal/domains/billing/tariff/`, `internal/domains/billing/outbox_tariff_event/` (эталон `stacks/go/examples/tariff`)

## Правила

- Никаких внешних зависимостей — без GORM тегов, без JSON тегов, без HTTP
- Ошибки возвращаются вверх (как обрабатывать — п. 20 `../checklist.md`, `exceptions.md`); HTTP-ответы формирует только entrypoint слой
- Нет вложенных пакетов с потерей контекста (например `services/dto`)
- Все DTO модуля — в `dto.go`; большой файл делится по `../conventions.md` п. 16 (`dto_create.go`, `dto_update.go`)

## Именование файлов

| Что | Файл |
|-----|------|
| Доменная сущность | `entity.go` |
| Интерфейсы данных — хранилища и внешние системы как источник данных (`Repository`, `CacheRepository`) | `repository.go` |
| Интерфейсы транзакций (`TxManager`) | `tx_manager.go` |
| Интерфейсы соседних модулей своего домена (O-6) | `modules.go` |
| Технические сервисы без данных домена — время, ID, отправка событий и сообщений, планирование (`Clock`, `IDGenerator`, `ChangeNotifier`, публикатор); спорный случай — `../conventions.md` п. 12 | `system.go` |
| Сервис | `service.go` |
| Константы модуля, в том числе типизированные перечисления | `enum.go` |
| Sentinel-ошибки домена (`var Err… = errors.New(...)`), которые проверяют через `errors.Is` | `errors.go` |
| Значение домена в `context.Context` (ключ — unexported тип, `With{X}`/`FromContext`) | `context.go` |
| DTO операций | `dto.go` |
| Сложная бизнес-логика изменения | `{конкретное_действие}_command.go` |
| Сложная бизнес-логика чтения | `{конкретное_действие}_query.go` |

## entity.go

Деньги — целым числом в минимальных единицах валюты (`regulations/entity.md`, «Value Object»), не `float64`; статус — свой тип из `enum.go`, поле неэкспортируемое: чтение — геттер, смена — методы перехода, сборка из БД — `Restore*` (`regulations/entity.md`, «Переход состояния»). Эталон — `../examples/tariff/internal/domains/billing/tariff/entity.go`.

```go
package product

// Product Товар каталога
type Product struct {
	ID   uint
	Name string
	// Price Цена в минимальных единицах валюты (тиын для KZT)
	Price      uint
	CategoryID uint
	status     Status
}

// RestoreProduct Сборка из БД — без проверок
func RestoreProduct(id uint, name string, price uint, categoryID uint, status Status) *Product {
	return &Product{ID: id, Name: name, Price: price, CategoryID: categoryID, status: status}
}

func (p *Product) Status() Status {
	return p.status
}

// CanDeactivate Снять с продажи можно только активный товар
func (p *Product) CanDeactivate() bool {
	return p.status == StatusActive
}

func (p *Product) Deactivate() error {
	if !p.CanDeactivate() {
		return ErrProductNotActive
	}

	p.status = StatusInactive

	return nil
}
```

## dto.go

```go
package product

// Search Фильтр списка: Page ≥ 1 (0 приводит маппер точки входа), PerPage 0 → 30 (умолчание хелпера пагинации SDK)
type Search struct {
	ID         uint
	Name       string
	CategoryID uint
	Page       uint
	PerPage    uint
}
```

## {entity}_validator.go — значения входа (O-18)

Проверки значений DTO без данных хранилища (диапазоны, связи полей); место — `regulations/entity.md` (O-26). Форма функции — по числу полей:

- **Многих полей** — возвращает карту ошибок по полям (`map[string]any`, пустая — корректно), исключение бросает вызывающий: ему нужны все ошибки сразу, а не первая. Так же — экспортируемый валидатор, который вызывает workflow (эталон — `ValidateTariff`, `ValidatePatch`).
- **Одного поля** (неэкспортируемый, вызывается в своём модуле) — сразу готовая 422: собирать карту из одного ключа и проверять её длину у вызывающего — лишний шаг.

```go
// плохо: одно поле — карта, которую вызывающий снова проверяет и оборачивает
func validateGrace(minutes uint) map[string]any {
	if minutes > MaxGraceMinutes {
		return map[string]any{"grace_minutes": "доп. время не больше суток"}
	}

	return nil
}

// хорошо
func validateGrace(minutes uint) error {
	if minutes > MaxGraceMinutes {
		return exception.NewValidationException(map[string]any{"grace_minutes": "доп. время не больше суток"}, false)
	}

	return nil
}
```

## repository.go — только интерфейсы

Все контракты хранилищ модуля лежат в одном файле `repository.go`. Если домен использует несколько хранилищ (БД, кеш, внешний API) — каждый контракт отдельным интерфейсом в том же файле. Интерфейсы транзакций — `tx_manager.go` (`infra/postgres.md`), остальные интерфейсы-зависимости модуля — `modules.go` и `system.go` (`../conventions.md` п. 12):

```go
package product

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
)

type Repository interface {
	Paginated(ctx context.Context, s *Search) (*pagination.Paginated[Product], error)
	GetByID(ctx context.Context, id uint) (*Product, error)
	Create(ctx context.Context, m *Product) (*Product, error)
	Update(ctx context.Context, m *Product) error
	Delete(ctx context.Context, id uint) error
}

type CacheRepository interface {
	Set(ctx context.Context, m *Product) error
	GetByID(ctx context.Context, id uint) (*Product, error)
	Invalidate(ctx context.Context, ids ...uint) error
}
```

Методы репозитория именуются произвольно — названия в примере условные.

Метод возвращает `nil, nil` если запись не найдена — не ошибку.

## service.go

```go
package product

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/pagination"
)

func NewService(repository Repository) *Service {
	return &Service{
		repository: repository,
	}
}

type Service struct {
	repository Repository
}

func (s *Service) GetByID(ctx context.Context, id uint) (*Product, error) {
	return s.repository.GetByID(ctx, id)
}

func (s *Service) Paginated(ctx context.Context, search *Search) (*pagination.Paginated[Product], error) {
	return s.repository.Paginated(ctx, search)
}

func (s *Service) Create(ctx context.Context, m *Product) (*Product, error) {
	return s.repository.Create(ctx, m)
}

func (s *Service) Update(ctx context.Context, m *Product) (*Product, error) {
	if err := s.repository.Update(ctx, m); err != nil {
		return nil, err
	}

	return m, nil
}
```

## enum.go — константы

Все константы модуля, в том числе типизированные перечисления, — в одном `enum.go`, блоками по смыслу: тип и его `const`-блок рядом. Константа, нужная одному файлу, может остаться в нём; константы Service, Command и Query — всегда в `enum.go`. `internal/common/` и `Common` домена — не модули: файл по понятию (`currency_enum.go`).

Значения перечисления — свой тип, не голый `int` (эталон — `../examples/tariff/internal/domains/billing/outbox_tariff_event/enum.go`):

```go
package product

// Status Статус товара
type Status uint

const (
	StatusActive Status = iota + 1
	StatusInactive
	StatusDeleted
)

// RoutingKeyProductChanged Ключ маршрутизации события изменения товара
const RoutingKeyProductChanged = "product.changed"
```

## Command / Query — сложная бизнес-логика

Если метод сервиса вырастает в сложную логику — выносить в отдельный файл.

Имя файла отражает конкретное действие, не общее:
- `recalculate_debt_command.go` (не `create_command.go`)
- `get_active_session_query.go` (не `get_query.go`)

**Command** — изменение данных:

```go
// recalculate_debt_command.go
type RecalculateDebtCommand struct {
	repository      Repository
	cacheRepository CacheRepository
}

func NewRecalculateDebtCommand(repository Repository, cacheRepository CacheRepository) *RecalculateDebtCommand {
	return &RecalculateDebtCommand{repository: repository, cacheRepository: cacheRepository}
}

func (c *RecalculateDebtCommand) Exec(ctx context.Context, dto RecalculateDebtDTO) (*Tariff, error) {
	// бизнес-логика
}
```

**Query** — чтение данных:

```go
// get_active_session_query.go
type GetActiveSessionQuery struct {
	repository      Repository
	cacheRepository CacheRepository
}

func NewGetActiveSessionQuery(repository Repository, cacheRepository CacheRepository) *GetActiveSessionQuery {
	return &GetActiveSessionQuery{repository: repository, cacheRepository: cacheRepository}
}

func (q *GetActiveSessionQuery) Exec(ctx context.Context, dto GetActiveSessionDTO) (*Session, error) {
	// логика с кешированием, фолбеком и тп
}
```

Разница с workflow: Command/Query живут внутри одного домена, workflow оркестрирует несколько доменов.

Command/Query берёт только репозитории **своего** домена; репозиторий или сервис чужого домена в конструкторе — тег `domain-cross-domain-access`, сценарий переносится в workflow (он — агрегат и берёт чужие репозитории напрямую, O-7).

Нужны несколько модулей своего домена — Command/Query лежит в модуле-владельце результата, остальные модули получает через интерфейсы, объявленные в этом модуле (решение O-6).

Если `Command` делает больше одного модифицирующего запроса (в разные таблицы или в одну) — оборачивать в транзакцию; один запрос — без неё, кроме транзакции ради блокировки `FOR UPDATE`/`FOR SHARE` — основание «Конкурентность» `design.md` (см. `../conventions.md`, правило 17, и `infra/postgres.md`, раздел "Транзакции").

## Cross-domain зависимость

Сервис, Command/Query домена чужой домен не берёт — ни репозиторий, ни сервис, ни интерфейс на чужой сервис (O-7, тег `domain-cross-domain-access`). Нужны данные или проверка по другому домену — workflow: берёт интерфейсы репозиториев/сервисов доменов напрямую (`parkingdomain.Repository`) и передаёт данные домену параметрами; свой интерфейс объявляет только для того, чего у доменов нет (`CreateTariffTxManager`). Эталон — `examples/tariff/internal/workflows/billing/tariff/create_tariff_workflow.go`:

```go
// плохо: сервис домена order с интерфейсом на чужой сервис
type Service struct {
	repository  Repository
	cityService CityServiceInterface
}

// хорошо: internal/workflows/order/order/create_order_workflow.go
type CreateOrderWorkflow struct {
	cityRepository  citydomain.Repository
	orderRepository orderdomain.Repository
}
```

