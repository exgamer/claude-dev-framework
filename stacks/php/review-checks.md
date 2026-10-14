# Ревью PHP: проверки сверх правил

Из `php-reviewer` (разделы 5.2–5.5). Общая процедура ревью — `approaches/process/review.md`; безопасность — `security.md`; теги находок — `review-tags.md`.

> Поправки фреймворка к таблице 5.2: кеш и загрузка файлов — по версии `mps/utils` проекта (`mps-core/capabilities.md`: `getOrCreate` единственный вариант до utils 2.6.4, `SmartCache` — с 2.8); «HTTP-класс в `Domains/`» — путь точки входа `Entrypoints/{Context}/{Domain}/{Module}` (P-9).

## 5.2 — Архитектурные нарушения (проверяй в первую очередь, из ``conventions.md`` и ``mps-core/rules.md``)

Отмечай как **[ОШИБКА]**, если найдено:

| Нарушение | Где искать |
|---|---|
| Бизнес-логика в Model или Repository (`if` по статусам, трансформация данных, вызов другого сервиса/репозитория, бизнес-исключение кроме `NotFoundAppException`) | Model, Repository |
| Толстый контроллер (логика вместо делегирования в Service/Command) | Controller |
| Прямой запрос к БД вне репозитория: `DB::`, `Model::where`/`query()`/`find`/`all()`, `->get()`/`->first()` по модели (`service-direct-db-query`, `conventions.md` п. 2) | Service, Command, Query, **Workflow** — проверка grep-ом в 5.5 п. 0 |
| Repository вызывает внешний сервис или другой Repository | Repository |
| `throw new \Exception` / `\RuntimeException` вместо `MPS\Core\Exceptions\*AppException` | любой слой |
| Стандартный `$app->singleton(Interface::class, Class::class)` вместо `definitions.php` | ServiceProvider, boot() |
| `RabbitManager::putIn(...)` вместо `QueueManagerInterface::putInQueue()` | любой слой |
| `QueryFilters` без `V2` неймспейса | Repository |
| `$cache->getOrCreate(...)` или `$cache->tags(...)->put(...)` вместо `SmartCache`/`getOrSet` | Service, Repository |
| `$fileManager->upload(...)` / `uploadFromUrl(...)` вместо `UploadManager` | любой слой |
| `$this->getLogger()->setMessagePrefix(...)` вместо `->for($this)` | любой слой |
| Отсутствует `declare(strict_types=1);` | любой PHP-файл |
| Цикл или обратная зависимость между доменами (см. `architecture/cross-domain.md`) | Domains |
| Новый HTTP-класс (Controller/Request/Response/Middleware) добавлен в `Domains/` вместо `Entrypoint/` | Domains |
| `ServiceInterface` сложного сервиса наследует `CRUDServiceInterface` | Services |
| Метод выполняет несколько операций записи через **разные** репозитории/сервисы (или несколько репозиторных методов подряд), где частичное выполнение недопустимо — но не обёрнут в транзакцию (см. `mps-core/transactions.md`) | Workflow (любая запись, O-8), Command, Service (кастомный метод с несколькими write-вызовами) |
| `$this->repository->transaction(...)` / `$this->someService->getRepository()->transaction(...)` вместо внедрённого `TransactionManagerInterface` — жёсткая связка: метод тянет транзакционность через чужой репозиторий вместо явной зависимости (см. `mps-core/transactions.md`) | Command, Service |
| `DB::transaction()` используется вместо `TransactionManagerInterface` без необходимости в SQL-специфике (см. `mps-core/transactions.md`, раздел «Как использовать») | Command, Service |

Workflow (`Workflows/`) с записью — всегда в транзакции, включая одиночный `create` (O-8, `approaches/patterns/transactions.md`); тег `workflow-without-transaction`.

Проверка атомарности (5.2, строка «Метод выполняет несколько операций записи...»), для Service/Command домена: одиночный `create`/`update`/`delete` через `CRUDService` уже обёрнут в транзакцию автоматически — не флагать. Флагать только когда **несколько** независимых repository/service вызовов на запись идут подряд в одном сценарии без общей транзакции.

## 5.3 — Дублирование бизнес-логики между похожими обработчиками

Встречается в проектах с несколькими похожими сценариями/типами/статусами: каждый обрабатывается своим Command/Job/методом с почти одинаковым скелетом (например: idempotency-check → вызов специфичного действия (`checkPayment`/`processPayment` и т.п.) → запись лога), но реализован копипастой в N файлах вместо общей абстракции.

**Признак:** N файлов (обычно Command или Job в `Domains/*/Modules/*/Commands|Jobs/`) почти дословно повторяют один и тот же порядок шагов, отличаясь только вызовом 1-2 специфичных методов внутри.

**Риск:** правка сквозного шага (например обработки ошибок или логирования) требует ручной синхронизации во всех N копиях — при следующем изменении велика вероятность забыть одну из копий.

Помечай как **[ВНИМАНИЕ]** (или **[ИНФО]**, если копий всего 2 и разница уже заметная) — не как **[ОШИБКА]**, это не блокирует мерж. В находке:
- укажи все файлы с повторяющимся скелетом
- предложи вынести общий скелет в переиспользуемую абстракцию (State Machine / Template Method: общий класс/метод с шагами `idempotencyCheck → action → writeLog`, где `action` — специфичное поведение, передаваемое через интерфейс, `match` по типу/статусу или DI)
- не настаивай на немедленном рефакторинге, если у автора PR это не в фокусе задачи — это находка для обсуждения, а не обязательное условие мержа

## 5.4 — Остальные правила

Применяй все правила из загруженных в Шагах 2–3 файлов (наименование, структура DTO, Enum, Command/Query, Swagger-аннотации, миграции, тесты, транзакции) и эмпирический список из ``review-findings.md``.

## 5.5 — Проверки за пределами дифа

Эти находки не видны в одном файле — их ловят по репозиторию и по данным. Выполняй при ревью дифа/MR; при `--all` — только пп. 0, 0a, 0b по всему проекту:

0. **Запросы к БД вне репозитория** — всегда, механически, не по памяти (кейс 2026-10-14: запросы в workflow прошли, потому что искали глазами «в сервисе»):
   ```
   grep -rnE "DB::|::(query|where|whereIn|whereKey|find|findOrFail|all|firstWhere|create|insert|updateOrCreate|firstOrCreate)\(" \
     Domains Contexts Workflows Entrypoints --include='*.php' | grep -vE "/(Repositories|Models)/|/Infrastructure/"
   ```
   Корни — папки проекта с кодом доменов (в `parking_app` они в корне подпроекта, в других — под `app/`): смотри `structure.md`. Паттерн ловит статические вызовы по модели (`Session::query()`, `User::where(...)`) и `DB::`; методы коллекций (`->pluck()`, `->first()`) намеренно не ищутся — шум. Каждая строка в `Services/`, `Commands/`, `Queries/`, `Workflows/`, `Validators/`, `Controllers/` — **[ОШИБКА]** `service-direct-db-query` (в Request — `request-queries-db`); `::create(`/`::all(` у DTO, enum, фабрик — не БД, отсеивать по классу. При ревью дифа — по файлам дифа и их соседям в модуле, при `--all` — по всему проекту. Проверено на эталоне `parking_app` 2026-10-14: одно срабатывание, и оно настоящее (`SessionEventService` → `Session::query()`), шума нет.
0a. **Повторённая обработка формата** (`duplicated-format-logic`, `core/principles.md`, «Повторяющаяся обработка — один хелпер»):
   ```
   grep -rnE "preg_replace\('/(\\\\D|\[\^0-9\]|\[\^\\\\d\])|str_starts_with\(.*'(\+7|8)'|substr\(\$(phone|iin|card)|'7' \. |mb_substr\(\$phone" \
     --include='*.php' . | grep -vE "/vendor/|/Helpers/|/tests/"
   ```
   Строка вне `Core/Helpers/` — кандидат: есть хелпер в проекте или шаблон в `MPS\Utils\Enums\RegexEnum` — **[ОШИБКА]**, второе вхождение без хелпера — **[ВНИМАНИЕ]**, одно и хелпера нет — **[ИНФО]** с предложением завести. В находке — все файлы с копиями.
0b. **Индекс массива без проверки** (`array-key-unchecked`, `conventions.md` п. 11b) — по файлам дифа, где есть массив снаружи (`array $payload`, `json_decode`, `config(`, `->all()`, `explode(`, `preg_match(`, `keyBy(`). Две команды (обычный `grep -E`, без `-P` — на macOS его нет):
   ```
   # обращение по ключу, кроме уже защищённых (??, isset, array_key_exists, empty, unset) и присваиваний
   grep -nE "\\\$[a-zA-Z_]+\[('[^']+'|\"[^\"]+\"|[0-9]+|\\\$[a-zA-Z_>-]+)\]" <файлы дифа> \
     | grep -vE "\?\?|isset\(|array_key_exists\(|empty\(|unset\(|\]\s*=[^=>]"
   # деструктуризация explode и группы preg_match
   grep -nE "\[\\\$[a-zA-Z_, \\\$]+\]\s*=\s*explode\(|preg_match\(" <файлы дифа>
   ```
   Это список кандидатов, не вердикт: по каждому — «откуда массив и проверен ли ключ выше». Внешний/динамический без `isset`/`array_key_exists`/`??`/проверки `count`/`=== 1` — **[ОШИБКА]**; собранный в той же функции, `validated()` по `required`-полю, индекс по результату `array_map`/`array_column` над проверенными данными — не находка. На эталоне `parking_app` команда даёт ~50 строк, из них настоящих единицы (`TimeHelper`: `$parts[0]`/`$parts[1]` после `explode(':')` без проверки формата). `composer phpstan` ловит `offsetAccess.notFound` только при известной форме массива — его прогон не заменяет проверку.
1. **Разъезд списков, новый статус без потребителя, гигиена MR** — правила общие, `approaches/process/review.md`, «За пределами дифа»; здесь только что искать в PHP: `in:a,b`/`Rule::in`/`Rule::enum` в других Request, `enum:` в `#[OA\…]`, словари во фронтовых эндпоинтах, `match` и мапперы по enum (`MediaCollectionMapper::MAP`); сгенерированные файлы, которые не должны уехать в MR, — `.php-cs-fixer.cache`, `storage/api-docs/*.json` (смотреть `git status --short`, не только HEAD).
2. **Данные под миграцию поведения** (правило — там же): новый каст модели, значение enum, `NOT NULL`, check-constraint —
   ```
   docker exec <контейнер-pg> psql -U <юзер> -d <база> -tAc \
     "select coalesce(col::text,'<NULL>'), count(*) from tbl group by 1 order by 2 desc;"
   ```
   Значение вне enum → `ValueError` при гидрации и 500 на **чтении**.
3. **Инструменты.** `composer phpstan` и `composer csfix-validate` в контейнере проекта (см. `composer.json` → `scripts`). Массовый `composer csfix` по репозиторию не запускать — репозитории не отформатированы целиком; значим только вывод по файлам дифа. Охват — `phpstan.neon` → `paths`, `.php-cs-fixer.php` → `in()`: диф вне охвата — писать это явно и прогонять хотя бы `php -l` по каждому файлу. Тесты в проекте локально не запускаются (битый `RefreshDatabase`) — сказать об этом.

Помечай каждую находку:
- **[БЕЗОПАСНОСТЬ]** — уязвимость из 5.1, всегда КРИТИЧНО
- **[ОШИБКА]** — нарушение правила, обязательно исправить до мержа
- **[ВНИМАНИЕ]** — отклонение от лучших практик, желательно исправить
- **[ИНФО]** — предложение по улучшению
