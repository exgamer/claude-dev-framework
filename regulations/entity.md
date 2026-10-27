# Доменная сущность: создание и валидация

Выжимка из `DOMAIN-ENTITY-CREATION.md` и `DOMAIN_ENTITY_VALIDATION.MD`.

**Итоговое правило: если объект существует — он валиден.**

**Где какое правило** (O-26): проверки значений входа — формат, диапазоны, связи полей DTO между собой — валидатор DTO `{E}DtoValidator` / `{entity}_validator.go` (O-18, `architecture/layers.md`, «Service, Command/Query, Workflow», строка Validator). Правила состояния и переходов (можно ли оплатить, отменить, архивировать) — методы модели домена: Go — методы сущности (`CanArchive()`/`Archive()`), PHP — методы Eloquent-модели (`canArchive()`; не мутаторы, модель — P-4). В Service, Command и Workflow таких проверок inline нет — они вызывают модель (`invariants-inline`). Почему: знание о статусах в одном месте; сервис, который сам сравнивает статусы, расходится с соседним при первой правке.

---

## Кто что проверяет

| Слой | Ответственность |
|---|---|
| Entrypoints (request DTO) | формат: обязательность, типы, длины, формат email/даты |
| Валидатор DTO (O-18) | значения входа: диапазоны, связи полей DTO между собой, без данных из БД |
| Модель домена (сущность Go, Eloquent-модель PHP) / Value Object | правила состояния и переходов; Value Object — правило одного значения |
| Domain Service / Workflow | проверки с обращением к миру: уникальность, существование, права, другие сервисы |
| Infrastructure / Mapper | трансформация, восстановление уже валидной сущности |
| Repository | только сохраняет и восстанавливает, не валидирует |

## Создание, сборка из БД, переход (Go)

```go
// NewOrder Начальный статус задаёт сущность, не запрос
func NewOrder(amount Money, userID uint) *Order {
    return &Order{amount: amount, userID: userID, status: StatusDraft}
}

// RestoreOrder Сборка из БД — без проверок (данные попали туда через домен)
func RestoreOrder(id uint, amount Money, userID uint, status Status) *Order {
    return &Order{id: id, amount: amount, userID: userID, status: status}
}

func (o *Order) Status() Status {
    return o.status
}

// CanCancel Отменить можно черновик и неоплаченный заказ
func (o *Order) CanCancel() bool {
    return o.status == StatusDraft || o.status == StatusAwaitingPayment
}

func (o *Order) Cancel() error {
    if !o.CanCancel() {
        return ErrOrderNotCancellable
    }

    o.status = StatusCancelled

    return nil
}
```

Mapper инфраструктуры вызывает `Restore*`, никогда `New*`.

## Формат, значения, состояние

- Формат (entrypoints): поле обязательно, длина ≤ 255, корректный email.
- Значения входа (валидатор DTO): сумма не отрицательная, период: конец позже начала.
- Состояние (модель): переход из текущего статуса допустим.

## Value Object

Правило про одно значение — в Value Object (`Money`, `Phone`, `Period`). Сущность принимает уже валидный тип, повторных проверок дальше нет. Нормализация формата (убрать пробелы, привести к `7XXXXXXXXXX`) — не инвариант, а хелпер на границе (`core/principles.md`, «Повторяющаяся обработка — один хелпер»); Value Object, если он есть, вызывает хелпер, а не повторяет регулярку.

Деньги — целым числом в минимальных единицах валюты (тиын, копейка, цент), не числом с плавающей точкой (`float64`, `float`): двоичная дробь теряет копейки при сложении и округлении. Так в обоих эталонах (`price` — цена часа в минимальных единицах).

## Поведение в сущности

Правило **про объект** — метод модели домена (`order.Cancel()` сам проверяет статус). Правило **процесса нескольких объектов** — Domain Service или Workflow. Знание о статусах объекта, размазанное по сервисам, — антипаттерн.

## Переход состояния

Каждый переход — своя Command, свой эндпоинт и свой метод модели (`Pay`, `Ship`, `Cancel`, `Archive`, `SetDefault`); общего `ChangeStatus`/`setStatus` со статусом аргументом нет (O-28). Почему: у перехода свои условия и побочные эффекты; общий метод сводит их в одно ветвление, и правка одного перехода задевает остальные (`core/style.md`, «Форма кода», O-25).

- Допустимость перехода проверяет модель (`CanArchive()`/`canArchive()`); Command вызывает её и условие не повторяет.
- Запись — метод репозитория этого перехода условным UPDATE (`… SET status = :new WHERE id = ? AND status = :expected`): 0 строк — запись уже изменили параллельно, 422 (`approaches/patterns/concurrency.md`, `race-on-status`).
- **Массовый переход** — много записей одним UPDATE без загрузки сущностей (отключение парковки: все её тарифы переходят разом — в Go-эталоне отключаются, `is_active`, в PHP-эталоне уходят в архив): метод модели по одной записи не вызывается; условие UPDATE — то же, что проверка допустимости в модели (`CanArchive()`/`canArchive()`: тот же набор статусов), запись — метод репозитория этого перехода (`archiveNotArchivedByParkingId`), Command — своя (`DeactivateParkingTariffsCommand`). Эталоны: Go — `stacks/go/examples/tariff/internal/domains/billing/tariff/deactivate_parking_tariffs_command.go`, PHP — `stacks/php/examples/parking_app/Domains/Billing/Modules/Tariffs/Commands/DeactivateParkingTariffsCommand.php`.
- Публичного сеттера статуса нет. Go: поле состояния неэкспортируемое, чтение — геттер, смена — методы перехода, сборка из БД — `Restore*`; записать мимо перехода не даст компилятор, экспортируемое поле состояния у новой сущности — `state-field-exported`. PHP: статус меняет только метод модели или метод репозитория конкретного перехода; `->status =`, `status` в `fill()`/`update()`/`create()` вне них — `status-set-directly`.
- Начальный статус при создании задаёт модель или фабрика (Go — `New*`), не Request.
- PATCH/PUT статус не меняют (`regulations/rest-api.md`, «Методы»): поле `status` или другого флага состояния в Request изменения, общий эндпоинт смены статуса — `status-in-update-request`.

```php
// Модель: допустимость перехода; набор статусов — подмножество enum (метод enum)
public function canArchive(): bool
{
    return in_array($this->status, TariffStatusEnum::notArchived(), true);
}

// Репозиторий: запись перехода одним условным UPDATE (то же условие, что canArchive()), false — статус уже сменили;
// переход снимает и флаг, который архивному не положен
public function archive(int $id): bool
{
    $updated = $this->getQuery()
        ->whereKey($id)
        ->whereIn('status', TariffStatusEnum::notArchivedValues())
        ->update([
            'status' => TariffStatusEnum::ARCHIVED->value,
            'is_default' => false,
        ]);

    return $updated > 0;
}
```

Эталон — `Tariff::canArchive()` и `TariffRepository::archive()` (`stacks/php/examples/parking_app/`).

## Когда применять

В существующих проектах сущности анемичные (публичные поля, без `New/Restore`, статус пишется сеттером) — **не переписывать массово**: правила этого файла — для нового кода и новых переходов; правишь переход — переводишь только его. Новый модуль с реальными правилами (деньги, статусы, периоды) — значения входа в валидаторе, состояние в модели с первого дня; неясно, где правило, — вопрос к пользователю (`core/principles.md`, «Пробелы и споры»).

**Сущность только для чтения** — сервис её только читает (справочник чужого домена или сервиса: парковка из справочника, администратор из identity), переходов её состояния в этом сервисе нет — ни метода, ни записи. У такой сущности поле состояния может быть открытым (Go — `IsActive`, `RevokedAt`): защищать нечего, запись идёт в сервисе-владельце. Появился переход в этом сервисе — с него действуют правила «Переход состояния». Сущность, чьё состояние сервис меняет (в том числе outbox со статусами), — не исключение.
