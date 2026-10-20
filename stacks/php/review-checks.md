# Ревью PHP: проверки сверх правил

Из `php-reviewer`. Общая процедура ревью — `approaches/process/review.md`; безопасность — `security.md`; теги находок и их уровни — `approaches/process/review-tags.md` (общий) и `review-tags.md` (PHP; O-24).

> Поправки фреймворка к таблице «Архитектурные нарушения»: кеш и загрузка файлов — по версии `mps/utils` проекта (`mps-core/capabilities.md`: `getOrCreate` единственный вариант до utils 2.6.4, `SmartCache` — с 2.8); «HTTP-класс в `Domains/`» — путь точки входа `Entrypoints/{Context}/{Domain}/{Module}` (P-9).

## Архитектурные нарушения (проверяй в первую очередь, из `conventions.md` и `mps-core/rules.md`)

Строка с тегом — уровень по каталогу тегов; строка без тега — **[ОШИБКА]** (временно, до заведения тега: O-24, бэклог):

| Нарушение | Где искать |
|---|---|
| Бизнес-логика в Model или Repository (`if` по статусам, трансформация данных, вызов другого сервиса/репозитория, любое бизнес-исключение, в т.ч. `NotFoundAppException` — репозиторий возвращает `null`, «не найдено» решает вызывающий, `mps-core/repository.md`) | Model, Repository |
| Толстый контроллер (логика вместо делегирования в Service/Command) | Controller |
| Прямой запрос к БД вне репозитория: `DB::`, `Model::where`/`query()`/`find`/`all()`, `->get()`/`->first()` по модели (`service-direct-db-query`, `conventions.md` п. 2) | Service, Command, Query, **Workflow** — проверка grep-ом — «Проверки за пределами дифа», п. 0 |
| Repository вызывает внешний сервис или другой Repository | Repository |
| `throw new \Exception` / `\RuntimeException` вместо `MPS\Core\Exceptions\*AppException` | любой слой |
| Стандартный `$app->singleton(Interface::class, Class::class)` вместо `definitions.php` | ServiceProvider, boot() |
| `RabbitManager::putIn(...)` вместо `QueueManagerInterface::putInQueue()` | любой слой |
| `QueryFilters` без `V2` неймспейса | Repository |
| Кеш не по версии `mps/utils` из `composer.lock` (`mps-core/rules.md`, «Cache»): `$cache->getOrCreate(...)`/`getCacheKeyByParts()` при utils ≥ 2.6.4 (там `getOrSet`/`KeyHelper::generate()`; при < 2.6.4 это единственный API — не находка); теги не через `SmartCache` (utils ≥ 2.8), в том числе `$cache->tags(...)->put(...)` Laravel — в любой версии | Service, Repository |
| `$fileManager->upload(...)` / `uploadFromUrl(...)` вместо `UploadManager` | любой слой |
| `$this->getLogger()->setMessagePrefix(...)` вместо `->for($this)` | любой слой |
| Отсутствует `declare(strict_types=1);` | любой PHP-файл |
| Цикл или обратная зависимость между доменами (см. `architecture/cross-domain.md`) | Domains |
| Новый HTTP-класс (Controller/Request/Response/Middleware) добавлен в `Domains/` вместо `Entrypoint/` | Domains |
| `ServiceInterface` сложного сервиса наследует `CRUDServiceInterface` | Services |
| Больше одного модифицирующего запроса без общей транзакции — тег `multi-write-without-transaction` (O-8, `approaches/patterns/transactions.md`) | Workflow, Command, Service |
| Своя транзакция вокруг одного модифицирующего запроса без блокировки по `concurrency.md` — тег `needless-transaction` | Workflow, Command, Service |
| `$this->repository->transaction(...)` / `$this->someService->getRepository()->transaction(...)` вместо внедрённого `TransactionManagerInterface` — жёсткая связка: метод тянет транзакционность через чужой репозиторий вместо явной зависимости (см. `mps-core/transactions.md`) | Command, Service |
| `DB::transaction()` вне реализации менеджера транзакций в `Infrastructure/` — в т.ч. ради SQL-специфики (уровень изоляции, savepoint): она уходит в реализацию `TransactionManagerInterface`, Command/Service знают только интерфейс (`approaches/patterns/transactions.md`, `mps-core/transactions.md`) | Command, Service |

Когда транзакция нужна и когда нет, что считать модифицирующим запросом, проверка перед записью — `approaches/patterns/transactions.md` (O-8).

Одиночный `create`/`update`/`delete` через `CRUDService` — один модифицирующий запрос, ядро оборачивает само: не флагать (`approaches/patterns/transactions.md`, «Не нужна»). Это только о транзакции: запись массивом через `create()`/`update()` ядра — `write-array-instead-of-dto` (`conventions.md` п. 11a).

## Дублирование бизнес-логики между похожими обработчиками

Правило, уровень и что писать в находке — `core/style.md`, «Форма кода» («Дублированный скелет обработчика»). Признак в PHP: N файлов (обычно Command или Job в `Domains/*/Modules/*/Commands|Jobs/`) почти дословно повторяют один порядок шагов; общий скелет — класс/метод с шагами, `action` — через интерфейс, `match` по типу/статусу или DI.

## Остальные правила

Применяй все правила из загруженных в Шагах 2–3 файлов (наименование, структура DTO, Enum, Command/Query, Swagger-аннотации, миграции, тесты, транзакции) и эмпирический список из `review-findings.md`.

Список (пагинация) — по `conventions.md` п. 2c (P-19), тег `list-form-not-as-designed`.

## Проверки за пределами дифа

Эти находки не видны в одном файле — их ловят по репозиторию и по данным. Выполняй при ревью дифа/MR; при `--all` — только пп. 0, 0a, 0b, 0c, 0d по всему проекту:

0. **Запросы к БД вне репозитория** — всегда, механически, не по памяти (кейс 2026-10-14: запросы в workflow прошли, потому что искали глазами «в сервисе»):
   ```
   grep -rnE "DB::|::(query|where|whereIn|whereKey|find|findOrFail|all|firstWhere|create|insert|updateOrCreate|firstOrCreate)\(" \
     Domains Contexts Workflows Entrypoints --include='*.php' | grep -vE "/(Repositories|Models)/|/Infrastructure/"
   ```
   Корни — папки проекта с кодом доменов (в подпроекте — в его корне, без подпроектов — под `app/`): смотри `structure.md`. Паттерн ловит статические вызовы по модели (`Session::query()`, `User::where(...)`) и `DB::`; методы коллекций (`->pluck()`, `->first()`) намеренно не ищутся — шум. Каждая строка в `Services/`, `Commands/`, `Queries/`, `Workflows/`, `Validators/`, `Controllers/` — `service-direct-db-query` (в Request — `request-queries-db`); `::create(`/`::all(` у DTO, enum, фабрик — не БД, отсеивать по классу. При ревью дифа — по файлам дифа и их соседям в модуле, при `--all` — по всему проекту. Прогон на PHP-подпроекте 2026-10-14: одно срабатывание, и оно настоящее (`SessionEventService` → `Session::query()`), шума нет.
0a. **Повторённая обработка формата** (`duplicated-format-logic`, `core/principles.md`, «Повторяющаяся обработка — один хелпер»):
   ```
   grep -rnE "preg_replace\('/(\\\\D|\[\^0-9\]|\[\^\\\\d\])|str_starts_with\(.*'(\+7|8)'|substr\(\$(phone|iin|card)|'7' \. |mb_substr\(\$phone" \
     --include='*.php' . | grep -vE "/vendor/|/Helpers/|/tests/"
   ```
   Строка вне `Core/Helpers/` — кандидат: случай (есть хелпер в проекте или шаблон в `MPS\Utils\Enums\RegexEnum`, вторая копия, одно вхождение без хелпера) и его уровень — `duplicated-format-logic` в каталоге; у одиночного — предложение завести хелпер. В находке — все файлы с копиями.
0b. **Индекс массива без проверки** (`array-key-unchecked`, `conventions.md` п. 11b) — по файлам дифа, где есть массив снаружи (`array $payload`, `json_decode`, `config(`, `->all()`, `explode(`, `preg_match(`, `keyBy(`). Две команды (обычный `grep -E`, без `-P` — на macOS его нет):
   ```
   # обращение по ключу, кроме уже защищённых (??, isset, array_key_exists, empty, unset) и присваиваний
   grep -nE "\\\$[a-zA-Z_]+\[('[^']+'|\"[^\"]+\"|[0-9]+|\\\$[a-zA-Z_>-]+)\]" <файлы дифа> \
     | grep -vE "\?\?|isset\(|array_key_exists\(|empty\(|unset\(|\]\s*=[^=>]"
   # деструктуризация explode и группы preg_match
   grep -nE "\[\\\$[a-zA-Z_, \\\$]+\]\s*=\s*explode\(|preg_match\(" <файлы дифа>
   ```
   Это список кандидатов, не вердикт: по каждому — «откуда массив и проверен ли ключ выше». Внешний/динамический без `isset`/`array_key_exists`/`??`/проверки `count`/`=== 1` — `array-key-unchecked`; собранный в той же функции, `validated()` по `required`-полю, индекс по результату `array_map`/`array_column` над проверенными данными — не находка. На PHP-подпроекте команда дала ~50 строк, из них настоящих единицы (`TimeHelper`: `$parts[0]`/`$parts[1]` после `explode(':')` без проверки формата). `composer phpstan` ловит `offsetAccess.notFound` только при известной форме массива — его прогон не заменяет проверку.
0c. **Константа со значением домена** (`constant-not-enum`, `conventions.md` п. 12b) — механически, не глазами (кейс mobile-api-rewrite, волна 9: константы типов услуги в репозитории прошли ревью, потому что правило читали как «только ключи»):
   ```
   # значения всех enum проекта
   grep -rhoE "case [A-Z_0-9]+ = '[^']+'" <код> --include='*Enum.php' | sed -E "s/.*= '([^']+)'/\1/" | sort -u > <tmp>/<ключ>-enum-values.txt
   # строковые константы классов (не enum) в файлах дифа
   grep -nE "(private|protected|public)? *const (string|array) [A-Z_0-9]+ = " <файлы дифа> | grep -v 'Enum.php'
   # списки кейсов enum константой (подмножество — метод enum)
   grep -nE "const array [A-Z_0-9]+ = \[" <файлы дифа> | grep "Enum::"
   ```
   По каждой константе: литерал (или элемент списка) есть в `<tmp>/<ключ>-enum-values.txt` (`<tmp>` — каталог временных файлов сессии, префикс — ключ задачи: `approaches/process/subagent.md`) и это тот же смысл (тип, статус, провайдер, действие) — `constant-not-enum` (повтор значения enum), в находке — enum своего домена или `Common/`, из которого брать (подходящий enum есть только в чужом домене — не импорт, а перенос в `Common/`, п. 0d); значение домена без enum — тот же тег (завести enum); уровни случаев — каталог PHP. Не находка: имена полей и фильтров (`'building_id'`), morph-тип легаси (`'App\Models\Building'`, `'user'`), настройки (числа, умолчания). Список кейсов enum константой (вторая команда) — `constant-not-enum` с предложением метода enum; такой же список уже есть в другом месте — копия (уровень — каталог). На mobile-api-rewrite первая команда дала 26 кандидатов, настоящих — около 12; вторая нашла `RESIDENCE_TYPES`/`PAYABLE_STATES`, которые первая пропустила.
0d. **Копии enum и импорт чужого домена** (`duplicated-enum`, `architecture/layers.md`, «Общий бизнес-словарь») — новый или изменённый enum в дифе сверяется с наборами значений всех enum проекта; дополнительно `grep -n "use .*\\\\Domains\\\\" <файлы домена в дифе>` — импорт из чужого домена:
   ```
   python3 - <<'PY'
   import re,glob
   e={f:set(re.findall(r"case [A-Z_0-9]+ = '([^']+)'",open(f).read())) for f in glob.glob('<код>/**/*Enum.php',recursive=True)}
   e={f:v for f,v in e.items() if v}; fs=list(e)
   for i,a in enumerate(fs):
       for b in fs[i+1:]:
           c=e[a]&e[b]
           if len(c)>=3 and len(c)/min(len(e[a]),len(e[b]))>=0.6: print(len(c),a,b)
   PY
   ```
   Пара, где один из файлов — в дифе, и это то же понятие — `duplicated-enum` (в находке — перенос в `Common/` и откуда импортировать); совпадение случайных значений разных понятий (поле анкеты и тип ссылки) — не находка. Импорт другого домена из `Domains/{D}/`: enum или value-объект — `duplicated-enum` с переносом в `Common/`, сервис или репозиторий — сценарий двух доменов, его место — workflow (O-7, `domain-cross-domain-access`).

   Зависимости `Common/` (бизнес-словарь без DI и I/O, `architecture/layers.md`, «Общий бизнес-словарь») — импорт слоёв подпроекта, фреймворка и ядра (кроме `Enumerable`/`EnumerableTrait`), фасады, `app()`:
   ```
   grep -rnE "^use (App\\\\[A-Za-z]+\\\\(Domains|Infrastructure|Workflows|Entrypoints|Core)\\\\|Illuminate\\\\|MPS\\\\)|DB::|app\(|resolve\(" <код>/Common --include='*.php' \
     | grep -vE "MPS\\\\Core\\\\Enums\\\\Enumerable(Trait)?;"
   ```
   Строка — `common-with-logic`; конструктор `Common/` с сервисом или репозиторием — то же (в эталоне это ловит тест `tests/Unit/ParkingApp/Architecture/CommonBoundaryTest.php`).
1. **Разъезд списков, новый статус без потребителя, гигиена MR** — правила общие, `approaches/process/review.md`, «За пределами дифа»; здесь только что искать в PHP: `in:a,b`/`Rule::in`/`Rule::enum` в других Request, `enum:` в `#[OA\…]`, словари во фронтовых эндпоинтах, `match` и мапперы по enum (`MediaCollectionMapper::MAP`); сгенерированные файлы, которые не должны уехать в MR, — `.php-cs-fixer.cache`, `storage/api-docs/*.json` (смотреть `git status --short`, не только HEAD).
2. **Данные под миграцию поведения** (правило — там же): новый каст модели, значение enum, `NOT NULL`, check-constraint —
   ```
   docker exec <контейнер-pg> psql -U <юзер> -d <база> -tAc \
     "select coalesce(col::text,'<NULL>'), count(*) from tbl group by 1 order by 2 desc;"
   ```
   Значение вне enum → `ValueError` при гидрации и 500 на **чтении**.
3. **Инструменты.** `composer phpstan` (уровень — `conventions.md` п. 23) и `composer csfix-validate` в контейнере проекта (см. `composer.json` → `scripts`). Массовый `composer csfix` по репозиторию не запускать — репозитории не отформатированы целиком; значим только вывод по файлам дифа. Охват — `phpstan.neon` → `paths`, `.php-cs-fixer.php` → `in()`: диф вне охвата — писать это явно и прогонять хотя бы `php -l` по каждому файлу. Тесты не запустились (нет базы, сломан `RefreshDatabase` и т. п.) — так и написать в отчёте с причиной; «не запускал» не выдаётся за «прошли».

Помечай каждую находку (находка с тегом — уровень по каталогу тегов):
- **[БЕЗОПАСНОСТЬ]** — уязвимость из `security.md`, всегда КРИТИЧНО
- **[ОШИБКА]** — нарушение правила, обязательно исправить до мержа
- **[ВНИМАНИЕ]** — отклонение от лучших практик, желательно исправить
- **[ИНФО]** — предложение по улучшению
