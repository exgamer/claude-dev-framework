#!/usr/bin/env bash
# Прогон PHP-эталона stacks/php/examples в docker-контейнере с PHP: phpunit, затем phpstan.
#
# Переменные:
#   PHP_CONTAINER  — имя запущенного контейнера с PHP >= 8.3 и composer (по умолчанию php-8.5)
#   COMPOSER_AUTH  — JSON авторизации composer с gitlab-token для git.mpinnovations.kz
#   MPS_AUTH_JSON  — или путь на хосте к auth.json с этим токеном (берётся, если COMPOSER_AUTH пуст);
#                    не задан — путь из tools/php-check/.auth-path (одна строка, в git не попадает)
#   TESTS_ONLY=1 / PHPSTAN_ONLY=1 — только одна из проверок
#
# --install — поставить vendor заново (нужен токен). Без флага vendor ставится, только если его нет
# или изменился composer.json. Токен в файлы стенда и в репозиторий не пишется.
# vendor живёт в контейнере (/tmp/df-php-check) и переиспользуется между прогонами.
set -euo pipefail

CONTAINER="${PHP_CONTAINER:-php-8.5}"
WORKDIR=/tmp/df-php-check
HERE="$(cd "$(dirname "$0")" && pwd)"
EXAMPLES="$HERE/../../stacks/php/examples"
FORCE_INSTALL=
[ "${1:-}" = "--install" ] && FORCE_INSTALL=1

dexec() { docker exec "$CONTAINER" sh -c "$1"; }

docker inspect -f '{{.State.Running}}' "$CONTAINER" 2>/dev/null | grep -q true \
    || { echo "Контейнер $CONTAINER не запущен (PHP_CONTAINER=...)" >&2; exit 2; }

OWNER="$(docker exec "$CONTAINER" id -u):$(docker exec "$CONTAINER" id -g)"

# Исходники — заново на каждый прогон, vendor и кеш phpstan остаются
dexec "mkdir -p $WORKDIR && cd $WORKDIR && rm -rf parking_app tests config stub phpunit.xml phpstan.neon"
docker cp "$EXAMPLES/parking_app" "$CONTAINER:$WORKDIR/parking_app"
docker cp "$EXAMPLES/tests" "$CONTAINER:$WORKDIR/tests"
docker cp "$EXAMPLES/config" "$CONTAINER:$WORKDIR/config"
docker cp "$HERE/tests/TestCase.php" "$CONTAINER:$WORKDIR/tests/TestCase.php"
docker cp "$HERE/stub" "$CONTAINER:$WORKDIR/stub"
for f in composer.json phpunit.xml phpstan.neon; do
    docker cp "$HERE/$f" "$CONTAINER:$WORKDIR/$f"
done
docker exec -u root "$CONTAINER" chown -R "$OWNER" "$WORKDIR"

SUM="$(dexec "md5sum $WORKDIR/composer.json | cut -d' ' -f1")"
if [ -n "$FORCE_INSTALL" ] || [ "$(dexec "cat $WORKDIR/.composer.sum 2>/dev/null || true")" != "$SUM" ] || ! dexec "test -d $WORKDIR/vendor"; then
    if [ -z "${COMPOSER_AUTH:-}" ] && [ -z "${MPS_AUTH_JSON:-}" ] && [ -f "$HERE/.auth-path" ]; then
        MPS_AUTH_JSON="$(head -1 "$HERE/.auth-path")"
        MPS_AUTH_JSON="${MPS_AUTH_JSON/#\~/$HOME}"
    fi
    if [ -z "${COMPOSER_AUTH:-}" ] && [ -n "${MPS_AUTH_JSON:-}" ]; then
        COMPOSER_AUTH="$(cat "$MPS_AUTH_JSON")"
    fi
    [ -n "${COMPOSER_AUTH:-}" ] || { echo "Нужен COMPOSER_AUTH, MPS_AUTH_JSON или tools/php-check/.auth-path (gitlab-token для git.mpinnovations.kz)" >&2; exit 2; }
    export COMPOSER_AUTH
    # Токен — через окружение docker exec (-e без значения), не в аргументах команды
    docker exec -e COMPOSER_AUTH -e COMPOSER_NO_INTERACTION=1 "$CONTAINER" \
        sh -c "cd $WORKDIR && rm -rf vendor composer.lock && composer update --no-progress --no-scripts -q"
    dexec "echo $SUM > $WORKDIR/.composer.sum"
else
    dexec "cd $WORKDIR && composer dump-autoload -q"
fi

STATUS=0
if [ -z "${PHPSTAN_ONLY:-}" ]; then
    echo "== phpunit"
    dexec "cd $WORKDIR && php vendor/bin/phpunit --no-progress" 2>&1 | grep -v 'Deprecated: PHP Startup' || STATUS=1
fi
if [ -z "${TESTS_ONLY:-}" ]; then
    echo "== phpstan"
    dexec "cd $WORKDIR && php -d memory_limit=2G vendor/bin/phpstan analyse --no-progress" 2>&1 | grep -v 'Deprecated: PHP Startup' || STATUS=1
fi
exit $STATUS
