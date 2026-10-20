# php-check — прогон PHP-эталона

Проверяет `stacks/php/examples` по-настоящему: phpunit (unit и feature) и phpstan (larastan, уровень — `stacks/php/conventions.md` п. 23). Запускать после каждой правки PHP-эталона (`skills/dev-rule/SKILL.md`, шаг 3а).

## Запуск

```bash
tools/php-check/run.sh --install   # поставить vendor заново (нужен токен)
tools/php-check/run.sh             # прогон; vendor ставится сам, только если его нет или изменился composer.json
```

Путь к `auth.json` с токеном — в `tools/php-check/.auth-path` (одна строка, `~` допустим; файл в `.gitignore`) или в `MPS_AUTH_JSON`. Claude Code запускает `--install` только после подтверждения пользователя: правило `ask` в `.claude/settings.local.json` (локально, в git не попадает).

- `PHP_CONTAINER` — запущенный контейнер с PHP >= 8.3 и composer (по умолчанию `php-8.5`).
- `COMPOSER_AUTH`, `MPS_AUTH_JSON` или `.auth-path` — `gitlab-token` для `git.mpinnovations.kz` (пакеты `mps/core`, `mps/utils`, `mps/elastic-query`). Нужен только при установке vendor: при первом запуске и после правки `composer.json`. В файлы стенда токен не пишется, в контейнер передаётся через окружение `docker exec`, не аргументом.
- `TESTS_ONLY=1` / `PHPSTAN_ONLY=1` — только одна проверка.
- Код возврата: `0` — всё прошло, `1` — упали тесты или phpstan, `2` — не готово окружение (контейнер не запущен, нет токена для установки vendor).
- Lock-файла нет: `mps/*` закреплены точно, а Laravel, testbench и larastan ставятся свежими в рамках `^` при каждой установке vendor.

Работа идёт в контейнере, в `/tmp/df-php-check`. Эталон копируется туда при каждом запуске, а `vendor` остаётся там между запусками. В репозиторий ничего не возвращается.

## Что стенд добавляет к эталону

Эталон — фрагмент приложения: часть того, что он использует, даёт хост (`stacks/php/examples/README.md`, «часть хоста»). Стенд закрывает это минимально:

| Файл | Что даёт |
|---|---|
| `tests/TestCase.php` | `Tests\TestCase` на testbench: провайдер подпроекта и логгер `mps/utils`, алиасы `verify.signature` и `api.response`, конфиги эталона `mps-utils`, `parking-app`, `services` (остальные `config/*.php` — фрагменты приложения, стенд их не грузит) |
| `stub/App/Http/Controllers/Controller.php` | базовый контроллер приложения |
| `phpstan.neon` | `ignoreErrors` только для шума типизации `mps/*` — с причиной у каждого |

Остальные алиасы хоста (`auth:admin-api`, `permission:*`, `resolve.admin.parkings`) общему `TestCase.php` не нужны: тест маршрутов Admin (`tests/Feature/ParkingApp/Admin/Billing/TariffRouteTest.php`) подменяет `permission` и `resolve.admin.parkings` у себя, `auth` снимает `withoutMiddleware`.

Не проверяется: миграции на живой БД, RabbitMQ и Redis (в тестах — моки и `array`-кеш).
