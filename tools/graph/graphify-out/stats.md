# Граф правил — сводка

Узлов 1206, связей 4895, групп 12. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 28
- возможности SDK/ядра — 26
- теги находок — 25
- транзакции — 25
- Конкурентность — 23
- Ошибки и исключения — 21
- unit-тесты — 20
- субагенты — 19
- безопасность — 19
- HTTP точка входа — 19
- Внешние интеграции — 19
- Авторизация и права — 19
- Подтверждение коммита/пуша — 19
- DI — 19
- RabbitMQ — 18
- валидация входа — 18
- именование — 18
- Swagger — 17
- хелперы и переиспользование — 17
- кэш — 16

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [approaches/process/bugfix.md § Границы] «Граница баг-фикса: больше одного модуля или ~больше 10 файлов без тестов → /dev» VS [approaches/process/planning.md § Размер задачи] «Предел задачи: один модуль одного домена, ≤2 соседних слоя, ориентир 8–10 файлов без тестов» (0.25)
C3. [core/backlog.md § B-99] «B-99: missing-rate-limiting расширить на любое угадываемое значение (промокод, купон, инвайт), отметка Go» VS [approaches/process/security.md § Каталог тегов] «missing-rate-limiting: логин, OTP, сброс пароля, регистрация без ограничения частоты (признаки — только PHP)» (0.75)
C4. [core/backlog.md § B-101] «B-101: загрузка файлов — allowlist по содержимому, запрет HTML/SVG/JS, Content-Disposition: attachment, nosniff» VS [approaches/process/security.md § Каталог тегов] «insecure-file-upload: файл под клиентским именем/расширением без проверки (только PHP)» (0.7)
C5. [core/backlog.md § D-9] «D-9: workflow.md разрешает интерфейс на сервис домена для подмены в тесте, contracts.md — «в Go бизнес-сервисы не мокаем»; рекомендация — конкретный тип» VS [approaches/process/testing.md § Правила] «Моки и фейки — только на границе (репозитории, адаптеры, шина, Clock, tx); бизнес-сервисы не мокаются» (0.3)
C6. [core/rules-evolution.md § Куда писать] «.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк» VS [core/principles.md § шапка] «При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»» (0.15)
C7. [core/style.md § Комментарии] «PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда» VS [core/style.md § Комментарии] «Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»» (0.15)
C8. [skills/dev/SKILL.md § Фаза 6] «Ревью df-review по git diff release; [ОШИБКА]/[БЕЗОПАСНОСТЬ] — свежим агентом; повторный круг по дельте; не больше 3 кругов» VS [skills/dev/SKILL.md § Фаза 6] «[ВНИМАНИЕ] с пометкой «унаследовано» попутно не исправляются — в отчёт и rules-log (review.md п. 3a)» (0.1)
C9. [stacks/go/examples/tariff/README.md § Событие tariff.created] «Событие tariff.created: все поля обязательны, null нет; at-least-once, дедуп по event_id» VS [stacks/php/examples/README.md § Событие tariff.created] «Событие tariff.created: null только у grace_minutes; ядро не ждёт подтверждения брокера (расхождение с Go — особенность примера)» (0.6)
C10. [stacks/go/structure.md § Структура директорий] «Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/» VS [architecture/layers.md § Направление зависимостей] «Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B» (0.1)
