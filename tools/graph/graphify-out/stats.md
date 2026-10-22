# Граф правил — сводка

Узлов 1232, связей 4998, групп 9. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- skills/dev-intro/SKILL.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 31
- возможности SDK/ядра — 29
- транзакции — 27
- теги находок — 25
- Конкурентность — 25
- Swagger — 24
- unit-тесты — 23
- слои — 23
- Ошибки и исключения — 22
- валидация — 22
- субагенты — 21
- подтверждение коммита/пуша — 21
- безопасность — 20
- миграции БД — 20
- HTTP точка входа — 19
- контракты — 19
- лог отклонений — 19
- ревью MR — 18
- workflow — 18
- Внешние интеграции — 18

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [approaches/process/review-tags.md § Производительность, кэш, списки] «list-filters-conflict: «умолчание производного — default в схеме OpenAPI, а не текстом» (читается как предписание)» VS [regulations/rest-api.md § Валидация входа] «Умолчание производного фильтра — текстом в описании параметра, без default в схеме OpenAPI; противоречивая пара — 422» (0.8)
C3. [core/style.md § Комментарии] «PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда» VS [core/style.md § Комментарии] «Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»» (0.15)
C4. [stacks/go/structure.md § Структура директорий] «Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/» VS [architecture/layers.md § Направление зависимостей] «Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B» (0.1)
