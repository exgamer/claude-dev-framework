# Граф правил — сводка

Узлов 1145, связей 4550, групп 10. Кандидаты ниже — подсказки, проверять по файлам.

## Сироты (на файл нет ссылок)
- agents/df-analyst.md
- agents/df-architect.md
- agents/df-go.md
- agents/df-php.md
- agents/df-qa.md
- agents/df-review.md
- agents/df-security.md
- skills/dev-mr/SKILL.md
- stacks/go/go-sdk/main.md
- stacks/go/go-sdk/module.md
- stacks/php/README.md
- stacks/vanilla-js/README.md

## Темы в большем числе файлов
- репозиторий — 27
- возможности SDK/ядра — 25
- транзакции — 24
- теги ревью — 23
- Ошибки и исключения — 21
- Конкурентность — 21
- субагенты — 20
- безопасность — 20
- unit-тесты — 19
- HTTP точка входа — 19
- validation rules — 19
- RabbitMQ — 18
- Внешние интеграции — 18
- Авторизация и права — 18
- Подтверждение коммита/пуша — 18
- именование — 18
- миграции БД — 17
- swagger — 17
- helpers reuse — 17
- DI — 17

## Кандидаты в противоречия (conflicts_with)
C1. [approaches/patterns/cache.md § список] «Инвалидация — при записи, в том же сценарии, что меняет данные» VS [approaches/patterns/transactions.md § «Общее»] «Внутри транзакции нет побочных эффектов: HTTP, брокер, файлы, dispatch, инвалидация кэша — после коммита» (0.15)
C2. [core/rules-evolution.md § Куда писать] «.claude/dev-rules.md проекта приоритетнее фреймворка в проекте; повтор в 2+ проектах — поднимать во фреймворк» VS [core/principles.md § шапка] «При конфликте побеждает более конкретное: стек > архитектура > принципы, кроме «Пробелы и споры»» (0.15)
C3. [core/style.md § Комментарии] «PHP: docblock класса обязателен — одна строка + @author; @property у модели всегда» VS [core/style.md § Комментарии] «Doc-комментарий — только если есть что сказать сверх имени; формат «Имя Описание по-русски»» (0.15)
C4. [stacks/go/examples/tariff/README.md § Событие tariff.created] «Событие tariff.created: все поля обязательны, null нет; at-least-once, дедуп по event_id» VS [stacks/php/examples/README.md § Событие tariff.created] «Событие tariff.created: null только у grace_minutes; ядро не ждёт подтверждения брокера (расхождение с Go — особенность примера)» (0.6)
C5. [stacks/go/structure.md § Структура директорий] «Bootstrap путь — bootstrap/{module}/ или bootstrap/{domain}/{module}/ по удобству; остальные слои всегда {domain}/{module}/» VS [architecture/layers.md § Направление зависимостей] «Запрещено: Domain → Infrastructure|Entrypoint|Workflow, Workflow → Entrypoint|Workflow, Infrastructure → Entrypoint, Entrypoint → Bootstrap, Context A → B» (0.1)
