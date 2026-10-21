# php-check — прогон PHP-эталона

Проверяет `stacks/php/examples` по-настоящему: phpunit (unit и feature), phpstan (larastan, уровень — `stacks/php/conventions.md` п. 23) и маршруты (`tools/lint/php_routes.py`, на хосте). Запускать после каждой правки PHP-эталона (`skills/dev-rule/SKILL.md`, шаг 3а).

## Запуск

```bash
tools/php-check/run.sh --install   # поставить vendor заново (нужен токен)
tools/php-check/run.sh             # прогон; vendor ставится сам, только если его нет или изменился composer.json
```

Путь к `auth.json` с токеном — в `tools/php-check/.auth-path` (одна строка, `~` допустим; файл в `.gitignore`) или в `MPS_AUTH_JSON`. Claude Code запускает `--install` только после подтверждения пользователя: правило `ask` в `.claude/settings.local.json` (локально, в git не попадает).

- `PHP_CONTAINER` — запущенный контейнер с PHP >= 8.3 и composer. Не задан — запомненный контейнер стенда; первый раз спросит из запущенных и запомнит в `tools/lint/.php-containers.json` (у разработчика, в git не попадает).
- `COMPOSER_AUTH`, `MPS_AUTH_JSON` или `.auth-path` — `gitlab-token` для `git.mpinnovations.kz` (пакеты `mps/core`, `mps/utils`, `mps/elastic-query`). Нужен только при установке vendor: при первом запуске и после правки `composer.json`. В файлы стенда токен не пишется, в контейнер передаётся через окружение `docker exec`, не аргументом.
- `TESTS_ONLY=1` / `PHPSTAN_ONLY=1` — только phpunit / только phpstan и маршруты.
- Код возврата: `0` — всё прошло, `1` — упали тесты, phpstan или проверка маршрутов (`tools/lint/php_routes.py`), `2` — не готово окружение (контейнер не запущен, нет токена для установки vendor).
- Lock-файла нет: `mps/*` закреплены точно, а Laravel, testbench и larastan ставятся свежими в рамках `^` при каждой установке vendor.

Работа идёт в контейнере, в `/tmp/df-php-check`. Эталон копируется туда при каждом запуске, а `vendor` остаётся там между запусками. В репозиторий ничего не возвращается.

## Что стенд добавляет к эталону

Эталон — фрагмент приложения: часть того, что он использует, даёт хост (`stacks/php/examples/README.md`, «часть хоста»). Стенд закрывает это минимально:

| Файл | Что даёт |
|---|---|
| `tests/TestCase.php` | `Tests\TestCase` на testbench: провайдер подпроекта и логгер `mps/utils`, алиасы `verify.signature` и `api.response`, конфиги эталона `mps-utils`, `parking-app`, `services` (остальные `config/*.php` — фрагменты приложения, стенд их не грузит) |
| `stub/App/Http/Controllers/Controller.php` | базовый контроллер приложения |
| `phpstan.neon` | `ignoreErrors` только для шума типизации `mps/*` — с причиной у каждого; подключает правила эталона `phpstan-rules.neon` (phpat — слои и домены `tests/Architecture/LayersArchitecture.php`; `phpstan-disallowed-calls` — фасад `DB` вне менеджера транзакций и миграций, `app()`/`resolve()` в бизнес-коде, `Auth`/`auth()` в домене и workflow, `env()` вне `config/` и `config.php` модуля, отладочный вывод; свои правила `tests/Architecture/*Rule.php`). Правило larastan `noEnvCallsOutsideOfConfig` выключено: `env()` проверяет правило фреймворка, а larastan считает конфигом каталог testbench, не `config/` эталона. Каждое правило проверено заведомым нарушением: `python3 tools/check/lint-proof/php.py` после прогона |

Остальные алиасы хоста (`auth:admin-api`, `permission:*`, `resolve.admin.parkings`) общему `TestCase.php` не нужны: тест маршрутов Admin (`tests/Feature/ParkingApp/Admin/Billing/TariffRouteTest.php`) подменяет `permission` и `resolve.admin.parkings` у себя, `auth` снимает `withoutMiddleware`.

Не проверяется: миграции на живой БД, RabbitMQ и Redis (в тестах — моки и `array`-кеш).
