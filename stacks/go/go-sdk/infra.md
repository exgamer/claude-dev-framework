# Infrastructure слой

Путь: `internal/infrastructure/{type}/…` — см. раскладку ниже.

Типы: `postgres`, `redis`, `http`, другие технологии (`jwt`, …)

## Раскладка по типу (решение G-2)

Принцип: **своё хранилище раскладывается по нашему домену, чужая система — по её имени.** Адаптер внешней системы описывает её API, модели и ошибки; нашим доменам он не принадлежит и может реализовывать интерфейсы нескольких доменов.

| Что | Путь | Пример |
|---|---|---|
| своя БД / кэш | `infrastructure/{postgres,redis}/{domain}/{module}/` | `postgres/billing/tariff` |
| внешний провайдер | `infrastructure/http/{provider}/{product}/`; у провайдера один API — просто `http/{provider}/` | `http/cityparking/pricelimits`, `http/partnerbilling/tariffs` (`examples/tariff`) |
| наш другой микросервис | `infrastructure/http/{service}/{module}/` | `http/{service}/{module}` (в эталоне нет) |
| другая технология (не HTTP) | `infrastructure/{tech}/{domain}/{module}/` | `jwt/identity/admin` |

- Группировка по типу услуги (`http/fiscalization/webkassa`) не используется — сразу провайдер.
- Если адаптер нужен двум доменам — каждый домен объявляет свой узкий интерфейс, один адаптер реализует оба.
- Имя пакета Go — имя провайдера/продукта (`autopay`, `webkassa`); при конфликте имён с доменом — алиас при импорте.


## Правила

- Реализует интерфейсы репозиториев из domain слоя (порт внешней системы — и из workflow, `workflow.md`) — не создаёт свои контракты
- Маппинг между инфра-моделью и доменной сущностью — только через `mapper.go`; прямых зависимостей структур между слоями нет
- Доменный слой не знает о существовании инфра-слоя
- Может использовать сущности и константы доменного слоя (`entity`, `enum.go`)
- **Маппер на `nil`** (тег `mapper-missing-nil-check`) — для мапперов всех слоёв (инфраструктура, точка входа): вход — указатель, выход — указатель: на `nil` сразу `return nil`, до обращения к полям; выход — значение: на `nil` — нулевое значение типа (`return tariffData{}`), выход-коллекция — пустая коллекция (`patchToColumns` эталона — `map[string]any{}`); вход — значение (Request в маппер точки входа передаётся по значению: `indexRequestToSearch(request indexRequest, …)`) — проверка не нужна, `nil` прийти не может. Почему: «не найдено» репозитория — `nil, nil`, маппер получает `nil` в обычном сценарии, обращение к полю — паника и 500.
  ```go
  // плохо: на nil — паника
  func tariffToData(tariff *tariffdomain.Tariff) *tariffData {
  	return &tariffData{ID: tariff.ID}
  }

  // хорошо
  func tariffToData(tariff *tariffdomain.Tariff) *tariffData {
  	if tariff == nil {
  		return nil
  	}

  	return &tariffData{ID: tariff.ID}
  }
  ```
- **Контракт репозитория** (только запросы, «не найдено» — `nil, nil`, без `FindOrCreate`, без бизнес-решений, таймаут на каждый запрос) — общее правило `architecture/layers.md`, «Правила слоёв». В Go: `ErrRecordNotFound` → `nil, nil`; таймаут — `context.WithTimeout(ctx, 10*time.Second)` в каждом методе (`infra/postgres.md`); пакетный `ON CONFLICT` — метод с именем по действию (`UpsertBatch`)

## Именование файлов

| Что | Файл |
|-----|------|
| Инфра модель (GORM / HTTP) | `model.go` |
| Реализация репозитория | `repository.go` |
| Маппинг model ↔ entity | `mapper.go` |
