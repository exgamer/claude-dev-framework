# Стек PHP: Laravel + mps/core + mps/utils

Для бэкенда на PHP. Всё в этой папке — **только про Laravel с ядром `mps/core` и `mps/utils`** (не про голый Laravel и не про Go: Go + Go SDK — `stacks/go/`).

| Файл | Что внутри |
|---|---|
| `checklist.md` | **памятка**: жёсткие правила одной строкой со ссылками — разработчик и ревью проходят её первой |
| `structure.md` | дерево подпроекта, пути, чеклист модуля, таблица соответствия Go SDK ↔ mps/core |
| `conventions.md` | обязательные правила слоёв, данных, ошибок, транзакций, HTTP |
| `style.md` | как автор пишет PHP: шапка класса, форма кода, имена |
| `review-findings.md` | реальные замечания из MR (superapp-api, parking_system) |
| `examples/` | эталонный модуль целиком по всем правилам (тарифы) |
| `mps-core/capabilities.md` | **что уже есть в mps/core и mps/utils**: задача → класс, с пометками версий |
| `mps-core/official/` | официальная документация `mps/core` v1.10.2 и `mps/utils` v2.8.4 (копия `docs/` пакетов) |
| `mps-core/reference/` | полный список классов и методов ядра по версиям (генерируется `tools/sdk-ref/php.sh`) |
| `mps-core/` | как пользоваться ядром: DataObject, CRUD-репозиторий/сервис, исключения, транзакции, очередь, кэш, Swagger, миграции, тесты |

Эталоны кода: `superapp-api/parking_app` (основной), `superapp-api/super_app` (второй). Легаси `superapp-api/app` — не эталон.

Порядок чтения разработчиком — `skills/dev-php/SKILL.md`, «Шаг 1»: памятка → `core/principles.md` → `structure.md` → по задаче нужные пункты `conventions.md`/`style.md` и файлы `mps-core/`. `review-findings.md` — материал ревьюера.
