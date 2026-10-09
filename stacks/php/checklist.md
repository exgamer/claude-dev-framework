# Памятка PHP (Laravel + mps/core)

Жёсткие правила одной строкой. Разработчик держит её открытой во время работы и проходит по ней на самопроверке; подробности и примеры — по ссылке, когда пункт касается задачи. Памятка не заменяет правила: при расхождении главнее файл по ссылке.

Уровень: **О** — [ОШИБКА], **В** — [ВНИМАНИЕ].

## Слои и место кода

1. **О** Файл лежит по `structure.md` (дерево подпроекта); `Infrastructure/{Тип}/…`; новый код не в легаси `app/`. — `structure.md`
2. **О** Контроллер тонкий: `Request` → `{E}Dto::make()->fromArray($request->validated())` → сервис/workflow → `response()->json(...)`; в сервис — DTO, не `validated()`/`all()`/массив. — `conventions.md` п. 1, 11a
3. **О** Service/Command/Query — только свой домен; 2+ домена → `Workflows/{D}/{M}/{Action}Workflow::execute()`; workflow берёт репозитории и сервисы любых доменов напрямую. — `conventions.md` п. 3 (O-7)
4. **О** Бизнес-логика — в сервисе/Command/workflow; репозиторий — только запросы через `$this->getQuery()`, без `keyBy`/`map`; в сервисе нет `DB::`/`Model::where`; запись в репозиторий — DTO или типизированные аргументы, не массив (`createFromDto`/`updateFromDto`/`setDefault`). — `conventions.md` п. 2, 11a, `review-findings.md`
5. **О** Интерфейс у каждого сервиса и репозитория; зависимости — конструктор, `private readonly`, тип — интерфейс; `app()`/`resolve()` в бизнес-коде нет. — `conventions.md` п. 5, 6
6. **О** DI — только `definitions.php` домена/инфраструктуры. — `conventions.md` п. 7
7. **О** Логика по контекстам различается → `{Context}{Entity}Service` в том же модуле, без `if ($isAdmin)`; сервисов в `Entrypoints/` нет. — `conventions.md`, «Логика, отличающаяся по контексту»
8. **О** Eloquent-связей в моделях нет; клиент внешней системы — `*Gateway` в `Infrastructure/Http/…`, конфиг через конструктор. — `conventions.md`, «Модели и внешние системы»
9. **В** Нет методов-обёрток над одной проверкой/вызовом (`findByIdOrFail`, `assertValid`). — `conventions.md` п. 12a

## Ядро

10. **О** Что есть в `mps/core`/`mps/utils`/`Core/` подпроекта — не пишется заново; устаревшее API (`RabbitManager::putIn`, `QueryFilters` V1, `getOrCreate`, `FileManager::upload`) — нет. Методы сверены со справочником версии из `composer.lock`. — `conventions.md` п. 21, 22, `mps-core/capabilities.md`

## Request и данные

11. **О** В Request нет SQL: ни `exists:`/`unique:`/`Rule::exists/unique`, ни своих правил/замыканий с моделью/`DB::`/репозиторием. Существование — в сервисе/workflow, уникальность — уникальный индекс + проверка в сервисе. — `conventions.md` п. 9a (P-17)
12. **О** `id`/`*_id` → `integer|gt:0`; у строк, чисел и массивов есть `max:`; допустимые значения — `Rule::enum()`; вложенная коллекция DTO — `list` + `{поле}.*: array`; бизнес-правил в Request нет. — `review-findings.md`, «Requests», `mps-core/data-objects.md`
13. **О** PUT — полные данные: в `UpdateRequest` каждое поле `required`, очищаемое — `present|nullable`. — `conventions.md` п. 12 (O-10)
14. **О** Роль/право на операцию — middleware на маршруте (`permission:…`/`role:…`), не `hasRole`/`authorize()`/`Gate::` в контроллере или домене. Доступ (здание/организация из заголовка или параметров) — middleware контекста → `$request->attributes` → `RequestHelper` → сервис получает фильтр; сервис и репозиторий о правах не знают. — `conventions.md` п. 19a
15. **В** Доменные инварианты — `{E}DtoValidator::validate($dto)` → `ValidationAppException('VALIDATION ERROR', $errors)`. — `conventions.md` п. 10 (P-8)
16. **О** Между слоями — DTO (`DataObject`, private-свойства, геттеры, fluent-сеттеры), не массив; enum-поле DTO — enum. — `conventions.md` п. 11
17. **В** Ключи (атрибуты, заголовки, теги кэша) — enum, не константы; константы в интерфейсах — **О**. — `conventions.md` п. 12b
18. **В** Список: `$sortAttributes` + сортировка по умолчанию в сервисе; каждое поле `filterRules()` применяется в `filterSearch()`. — `conventions.md` п. 12c, 12d

## Транзакции и побочные эффекты

19. **О** Workflow с записью — проверки и все записи в одном `TransactionManagerInterface::run()`; без транзакции — только с причиной в `design.md`. — `approaches/patterns/transactions.md` (O-8)
20. **О** Service/Command с 2+ связанными записями — `run()`; не `DB::transaction`, не `$repo->transaction`. — `conventions.md` п. 16
21. **О** Очередь, `Job::dispatch`, HTTP, файлы, сброс кэша — после записи и вне транзакции. — `conventions.md` п. 17

## Ошибки

22. **О** Только исключения ядра (`NotFoundAppException`, `ValidationAppException`, …), не `\Exception`/`\RuntimeException`; сообщения клиенту — по-русски; `@throws` у бросающих методов. — `conventions.md` п. 13–15
23. **О** `catch` не глотает: rethrow или `AppException` с контекстом; fail консольной команды/крона — в Sentry.

## HTTP

24. **О** OpenAPI-атрибуты на каждом эндпойнте, с `404`/`500`; тег `'{Context} {Подпроект}: {Сущность}'`. — `conventions.md` п. 18
25. **О** Ответ — `response()->json(new {E}Response(...), 200|201)`, удаление — `204`; обёртку `success/data` не собирать (её даёт `ApiResponseMiddleware`); макросы `Response::ok()` — нет. — `conventions.md` п. 19
26. **В** Маршруты: префикс, имена `{ресурсы}.{index|view|create|update|delete}`, kebab-case, middleware — строковым алиасом. — `conventions.md` п. 20

## Конкурентность, производительность, безопасность

27. **О** Конкурентные места из `design.md` закрыты: уникальный индекс, условная смена статуса, идемпотентность, блокировка крона; состояния в памяти процесса нет (Octane, 2+ инстанса). — `approaches/patterns/concurrency.md`
28. **О** Без N+1, пагинация, индекс под фильтр/сортировку, большие выборки — `chunk()`/`cursor()`. — `approaches/patterns/performance.md`
29. **О** Мульти-тенантный скоуп в каждом методе (`index`/`show`/`update`/`destroy` по отдельности): параметр владельца выводится сервером, не берётся из запроса как необязательный; в сигнатуре сервиса/репозитория скоуп — без умолчания `= null`, `null` передаётся явно. — `security.md`, `review-findings.md`
30. **О** Вебхук — проверка подписи до смены статуса; повтор события идемпотентен. — `security.md`
31. **О** Нет сырого SQL с подстановкой ввода, секретов в коде и логах, mass assignment без allowlist. — `security.md`
32. **О** Меры из `threats.md` по своим файлам реализованы.

## Оформление

33. **О** Используемые расширения PHP (`ext-zip`, `ext-intl`…) объявлены в `require` `composer.json` (AINA-2744). `declare(strict_types=1);` в каждом файле; форматирование — `composer csfix-validate`.
34. **В** Docblock класса — одна строка по-русски + `@author`; описания, пересказывающие имя, и «ИИ-шные» комментарии не писать. — `style.md`, `core/style.md`
35. **В** `final` у Command/Query/workflow/валидаторов; promotion `private readonly`, trailing comma. — `style.md`
36. **В** Имена по таблице `style.md` (`{Entity}Dto`, `{Entity}CrudService`, `{Action}Workflow`, enum `UPPER_SNAKE` + `Enumerable`).
37. **О** Модель: `@property` по-русски, `$table` со схемой (таблица в `public` — без префикса), `$fillable`, `$casts` (enum → enum). — `style.md`

## Тесты и проверка

38. **О** Unit-тесты на правила R*, ветки ошибок и кейсы `qa.md` уровня unit — в `tests/Unit/…`, путь повторяет путь класса. — `structure.md`, «Тесты»
39. **О** Прогнаны `composer csfix-validate`, `composer phpstan`, `composer test:run` (или аналоги проекта); вывод показан. Не запускал — так и написано.
40. **О** Связи: по каждому изменённому классу/роуту/таблице/enum найдены использования (имя + строковый литерал); новое значение enum — в `in:`, swagger `enum:`, словарях фронта. — `approaches/process/impact-analysis.md`
41. **О** Миграция обратно совместима (expand/contract), порядок выкатки описан. — `approaches/patterns/migrations.md`

## Действия агента

42. **О** Миграции, сиды, массовые правки, очистка, `migrate:fresh`/`RefreshDatabase` на общей БД — не запускать без явного «да» на каждую. — `core/principles.md`, «Действия с БД»
43. **О** Пробел или спор — не решать молча: вопрос или решение по политике `/dev` + строка в `rules-log.md`. — `core/principles.md`
44. **О** Коммит и пуш — только по явному «да» на каждый.
