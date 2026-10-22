# Граф правил — сводка

Узлов 1208, связей 4900, групп 9. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 30
- возможности SDK/ядра — 28
- транзакции — 27
- теги находок — 25
- Конкурентность — 25
- unit-тесты — 24
- Swagger — 24
- Ошибки и исключения — 22
- слои — 22
- валидация — 21
- субагенты — 20
- миграции БД — 20
- подтверждение коммита/пуша — 20
- HTTP точка входа — 19
- безопасность — 18
- workflow — 18
- Внешние интеграции — 18
- контракты — 18
- лог отклонений — 18
- хелперы и переиспользование — 18

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [approaches/process/review-tags.md § Производительность, кэш, списки] «list-filters-conflict: «умолчание производного — default в схеме OpenAPI, а не текстом» (читается как предписание)» VS [regulations/rest-api.md § Валидация входа] «Умолчание производного фильтра — текстом в описании параметра, без default в схеме OpenAPI; противоречивая пара — 422» (0.8)
C3. [core/backlog.md § D-3] «D-3: «не делать: предел шагов ниже 120, лимит кругов ревью» (читается как отсутствие лимита при пределе 3 круга)» VS [approaches/process/review.md § Отчёт] «Вердикт: ПРОЙДЕНО — 0 ошибок; НУЖНЫ ПРАВКИ — 1–3 ошибки; КРИТИЧНО — 1+ [БЕЗОПАСНОСТЬ] или 4+ ошибок или запрещённая зависимость слоёв» (0.2)
C4. [core/backlog.md § B-87] «B-87: служебные пути /healthcheck/* вне схемы URL /api/... — нужна оговорка в rest-api.md» VS [regulations/rest-api.md § URL] «URL: /api/<context>/<version>/<domain>/<module>/<entity>/<action?>; version v1; kebab-case; сущность во мн. числе» (0.3)
C5. [core/style.md § Комментарии] «PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда» VS [core/style.md § Комментарии] «Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»» (0.15)
C6. [stacks/go/structure.md § Структура директорий] «Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/» VS [architecture/layers.md § Направление зависимостей] «Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B» (0.1)
