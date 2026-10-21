#!/usr/bin/env python3
"""Доказательство для правил PHP-эталона (`tests/Architecture/*`, `phpstan-rules.neon`) и проверки маршрутов
(`tools/lint/php_routes.py`): каждое ловит заведомое нарушение, разрешённое (Eloquent-модель в домене, P-4; Common домена
из его модуля) — не ловит. Маршруты проверяются на копии эталона во временном каталоге с одной заменой текста — без PHP; режим `--base` —
на временном git-репозитории (старая строка изменённого routes.php — не находка, новая — находка).

Запуск из корня репозитория после `tools/php-check/run.sh` (стенд с vendor уже в контейнере):
    python3 tools/check/lint-proof/php.py [PHP_CONTAINER]
Нарушение кладётся файлом в рабочую копию стенда в контейнере, анализируется только этот файл, файл удаляется.
"""
import os
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), "../../lint"))
import php_container  # noqa: E402
import php_routes  # noqa: E402

CONTAINER = php_container.resolve(None, sys.argv[1] if len(sys.argv) > 1 else None) or sys.exit(php_container.hint())
WORKDIR = "/tmp/df-php-check"
R = "App\\ParkingApp"
CASES = [  # (имя, путь в parking_app, код класса, ожидаемый идентификатор или None — нарушения быть не должно)
    ("домен → Infrastructure", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function __construct(private readonly \\{R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Repositories\\TariffRepository $r) {{}} }}",
     "testDomainDoesNotKnowOuterLayers"),
    ("домен → Infrastructure через new", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ new \\{R}\\Infrastructure\\Http\\CityParking\\DTO\\PriceLimitResponseDto(); }} }}",
     "testDomainDoesNotKnowOuterLayers"),
    ("домен → Infrastructure через ::class", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nuse {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Repositories\\TariffRepository;\nfinal class ZzV {{ public function f(): string {{ return TariffRepository::class; }} }}",
     "testDomainDoesNotKnowOuterLayers"),
    ("домен → Entrypoints", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(\\{R}\\Entrypoints\\Admin\\Billing\\Tariffs\\Http\\Requests\\Tariffs\\IndexRequest $r): void {{}} }}",
     "testDomainDoesNotKnowOuterLayers"),
    ("домен → Workflows", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(\\{R}\\Workflows\\Billing\\Tariffs\\CreateTariffWorkflow $w): void {{}} }}",
     "testDomainDoesNotKnowOuterLayers"),
    ("Billing → Catalog", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(\\{R}\\Domains\\Catalog\\Modules\\Parkings\\Repositories\\ParkingRepositoryInterface $p): void {{}} }}",
     "testBillingDoesNotKnowOtherDomains"),
    ("Catalog → Billing", "Domains/Catalog/Modules/Parkings/ZzV.php",
     f"namespace {R}\\Domains\\Catalog\\Modules\\Parkings;\nfinal class ZzV {{ public function f(\\{R}\\Domains\\Billing\\Modules\\Tariffs\\Services\\TariffCrudServiceInterface $s): void {{}} }}",
     "testCatalogDoesNotKnowOtherDomains"),
    ("модуль → соседний модуль домена", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(\\{R}\\Domains\\Billing\\Modules\\OutboxTariffEvents\\Repositories\\OutboxTariffEventRepositoryInterface $r): void {{}} }}",
     "testBillingTariffsDoesNotKnowOtherModules"),
    ("модуль → соседний модуль домена (OutboxTariffEvents)", "Domains/Billing/Modules/OutboxTariffEvents/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\OutboxTariffEvents;\nfinal class ZzV {{ public function f(\\{R}\\Domains\\Billing\\Modules\\Tariffs\\Enums\\TariffStatusEnum $s): void {{}} }}",
     "testBillingOutboxTariffEventsDoesNotKnowOtherModules"),
    ("Common домена → модуль домена", "Domains/Billing/Common/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Common;\nfinal class ZzV {{ public function f(\\{R}\\Domains\\Billing\\Modules\\Tariffs\\Enums\\TariffStatusEnum $s): void {{}} }}",
     "testBillingCommonDoesNotKnowModules"),
    ("разрешено: модуль → Common своего домена", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(\\{R}\\Domains\\Billing\\Common\\Enums\\OutboxTariffEventStatusEnum $s): void {{}} }}",
     None),
    ("Common → Domains", "Common/ZzV.php",
     f"namespace {R}\\Common;\nfinal class ZzV {{ public function f(\\{R}\\Domains\\Billing\\Modules\\Tariffs\\Services\\TariffCrudServiceInterface $s): void {{}} }}",
     "testCommonHasNoLayerDependencies"),
    ("workflow → workflow", "Workflows/Billing/Tariffs/ZzVWorkflow.php",
     f"namespace {R}\\Workflows\\Billing\\Tariffs;\nfinal class ZzVWorkflow {{ public function __construct(private readonly CreateTariffWorkflow $w) {{}} }}",
     "testWorkflowDoesNotCallWorkflow"),
    ("точка входа → Infrastructure", "Entrypoints/Admin/Billing/Tariffs/ZzV.php",
     f"namespace {R}\\Entrypoints\\Admin\\Billing\\Tariffs;\nfinal class ZzV {{ public function __construct(private readonly \\{R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Repositories\\TariffRepository $r) {{}} }}",
     "testEntrypointDoesNotKnowInfrastructure"),
    ("фасад DB (DB::transaction) вне менеджера", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ \\Illuminate\\Support\\Facades\\DB::transaction(static fn () => null); }} }}",
     "DB is forbidden"),
    ("фасад DB через глобальный алиас", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ \\DB::table('x'); }} }}",
     "DB is forbidden"),
    ("app() в бизнес-коде", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ app('cache'); }} }}",
     "app()"),
    ("разрешено: фасад DB в репозитории Infrastructure", "Infrastructure/Postgres/Billing/Tariffs/Repositories/ZzV.php",
     f"namespace {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Repositories;\nfinal class ZzV {{ public function f(): void {{ \\Illuminate\\Support\\Facades\\DB::raw('1'); }} }}",
     None),
    ("разрешено: фасад DB в миграции", "Infrastructure/Postgres/Billing/Providers/database/migrations/zz_v.php",
     "return new class () { public function up(): void { \\Illuminate\\Support\\Facades\\DB::statement('select 1'); } };",
     None),
    ("new RuntimeException в сервисе", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ throw new \\RuntimeException('x'); }} }}",
     "devFramework.exceptionNotDomainType"),
    ("new Exception через use", "Workflows/ZzV.php",
     f"namespace {R}\\Workflows;\nuse Exception;\nfinal class ZzV {{ public function f(): void {{ throw new Exception('x'); }} }}",
     "devFramework.exceptionNotDomainType"),
    ("new InvalidArgumentException", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ throw new \\InvalidArgumentException('x'); }} }}",
     "devFramework.exceptionNotDomainType"),
    ("throw_if со встроенным исключением", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(bool $b): void {{ throw_if($b, \\LogicException::class, 'x'); }} }}",
     "devFramework.exceptionNotDomainType"),
    ("разрешено: исключение ядра", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nuse MPS\\Core\\Exceptions\\NotFoundAppException;\nfinal class ZzV {{ public function f(): void {{ throw new NotFoundAppException('x'); }} }}",
     None),
    ("разрешено: catch Exception", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ try {{ $this->g(); }} catch (\\Exception $e) {{ unset($e); }} }} private function g(): void {{}} }}",
     None),
    ("разрешено: new Exception в тестах", "../tests/Unit/ZzV.php",
     "namespace Tests\\Unit;\nfinal class ZzV { public function f(): \\Exception { return new \\Exception('x'); } }",
     None),
    ("разрешено: фасад DB в тестах", "../tests/Unit/ZzV.php",
     "namespace Tests\\Unit;\nfinal class ZzV { public function f(): void { \\Illuminate\\Support\\Facades\\DB::table('x'); } }",
     None),
    ("разрешено: модель Eloquent в домене (P-4)", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(\\{R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models\\Tariff $t): void {{}} }}",
     None),
    ("status модели вне перехода", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nuse {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models\\Tariff;\nuse {R}\\Domains\\Billing\\Modules\\Tariffs\\Enums\\TariffStatusEnum;\nfinal class ZzV {{ public function f(Tariff $t): void {{ $t->status = TariffStatusEnum::ARCHIVED; }} }}",
     "devFramework.statusSetDirectly"),
    ("is_default в fill() модели", "Workflows/ZzV.php",
     f"namespace {R}\\Workflows;\nuse {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models\\Tariff;\nfinal class ZzV {{ public function f(Tariff $t): void {{ $t->fill(['is_default' => true]); }} }}",
     "devFramework.statusSetDirectly"),
    ("status в Model::create()", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nuse {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models\\Tariff;\nfinal class ZzV {{ public function f(): void {{ Tariff::create(['name' => 'x', 'status' => 'active']); }} }}",
     "devFramework.statusSetDirectly"),
    ("status в update() репозитория из сервиса", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nuse {R}\\Domains\\Billing\\Modules\\Tariffs\\Repositories\\TariffRepositoryInterface;\nfinal class ZzV {{ public function f(TariffRepositoryInterface $r): void {{ $r->update(1, ['status' => 'archived']); }} }}",
     "devFramework.statusSetDirectly"),
    ("разрешено: status в методе репозитория", "Infrastructure/Postgres/Billing/Tariffs/Repositories/ZzVRepository.php",
     f"namespace {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Repositories;\nuse {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models\\Tariff;\nfinal class ZzVRepository {{ public function archive(int $id): int {{ return Tariff::query()->whereKey($id)->update(['status' => 'archived']); }} }}",
     None),
    ("разрешено: status в методе модели", "Infrastructure/Postgres/Billing/Tariffs/Models/ZzV.php",
     f"namespace {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models;\n/** @property string $status\n * @property bool $is_default */\nfinal class ZzV extends \\Illuminate\\Database\\Eloquent\\Model {{ public function initState(): void {{ $this->status = 'active'; $this->is_default = false; }} }}",
     None),
    ("разрешено: начальный статус в фабрике", "Domains/Billing/Modules/Tariffs/ZzVFactory.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nuse {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models\\Tariff;\nuse {R}\\Domains\\Billing\\Modules\\Tariffs\\Enums\\TariffStatusEnum;\nfinal class ZzVFactory {{ public function make(): Tariff {{ $t = new Tariff(); $t->status = TariffStatusEnum::ACTIVE; return $t; }} }}",
     None),
    ("разрешено: status у DTO", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ private string $status = ''; public function setStatus(string $s): self {{ $this->status = $s; return $this; }} public function getStatus(): string {{ return $this->status; }} }}",
     None),
    ("Eloquent-связь в модели", "Infrastructure/Postgres/Billing/Tariffs/Models/ZzV.php",
     f"namespace {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models;\nuse Illuminate\\Database\\Eloquent\\Relations\\HasMany;\nfinal class ZzV extends \\Illuminate\\Database\\Eloquent\\Model {{ public function tariffs(): HasMany {{ return $this->hasMany(Tariff::class); }} }}",
     "devFramework.modelHasEloquentRelations"),
    ("Eloquent-связь без объявленного типа", "Infrastructure/Postgres/Billing/Tariffs/Models/ZzV.php",
     f"namespace {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models;\nfinal class ZzV extends \\Illuminate\\Database\\Eloquent\\Model {{ public function tariff() {{ return $this->belongsTo(Tariff::class); }} }}",
     "devFramework.modelHasEloquentRelations"),
    ("разрешено: метод модели без связи", "Infrastructure/Postgres/Billing/Tariffs/Models/ZzV.php",
     f"namespace {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models;\nfinal class ZzV extends \\Illuminate\\Database\\Eloquent\\Model {{ public function canArchive(): bool {{ return $this->getKey() !== null; }} }}",
     None),
    ("Auth:: в сервисе домена", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nuse Illuminate\\Support\\Facades\\Auth;\nfinal class ZzV {{ public function f(): mixed {{ return Auth::id(); }} }}",
     "Auth is forbidden"),
    ("Auth через глобальный алиас в workflow", "Workflows/ZzV.php",
     f"namespace {R}\\Workflows;\nfinal class ZzV {{ public function f(): mixed {{ return \\Auth::id(); }} }}",
     "Auth is forbidden"),
    ("auth() в workflow", "Workflows/ZzV.php",
     f"namespace {R}\\Workflows;\nfinal class ZzV {{ public function f(): mixed {{ return auth()->id(); }} }}",
     "auth()"),
    ("разрешено: Auth в точке входа", "Entrypoints/Admin/Billing/Tariffs/ZzV.php",
     f"namespace {R}\\Entrypoints\\Admin\\Billing\\Tariffs;\nfinal class ZzV {{ public function f(): mixed {{ return \\Illuminate\\Support\\Facades\\Auth::id(); }} }}",
     None),
    ("env() вне config", "Infrastructure/Http/CityParking/ZzV.php",
     f"namespace {R}\\Infrastructure\\Http\\CityParking;\nfinal class ZzV {{ public function f(): mixed {{ return env('CITY_PARKING_URL'); }} }}",
     "env()"),
    ("разрешено: env() в config", "../config/zz_v.php",
     "return ['url' => env('CITY_PARKING_URL')];",
     None),
    ("разрешено: env() в config.php модуля", "Domains/Billing/Modules/Tariffs/config.php",
     "return ['url' => env('CITY_PARKING_URL')];",
     None),
    ("env() в сервисе рядом с config.php модуля", "Domains/Billing/Modules/Tariffs/Services/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs\\Services;\nfinal class ZzV {{ public function f(): mixed {{ return env('CITY_PARKING_URL'); }} }}",
     "env()"),
    ("dd() в коде", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(int $x): void {{ dd($x); }} }}",
     "dd()"),
    ("dump() в коде", "Entrypoints/Admin/Billing/Tariffs/ZzV.php",
     f"namespace {R}\\Entrypoints\\Admin\\Billing\\Tariffs;\nfinal class ZzV {{ public function f(int $x): void {{ dump($x); }} }}",
     "dump()"),
    ("var_dump() в коде", "Infrastructure/Http/CityParking/ZzV.php",
     f"namespace {R}\\Infrastructure\\Http\\CityParking;\nfinal class ZzV {{ public function f(int $x): void {{ var_dump($x); }} }}",
     "var_dump()"),
]

EXAMPLE = Path(__file__).resolve().parents[3] / "stacks/php/examples/parking_app"
ROUTE_CASES = [  # (имя, файл в parking_app, было, стало, ожидаемый тег или None)
    ("api.response не первым в группе", "Entrypoints/Admin/Billing/routes.php", "    'api.response',\n    'auth:admin-api',", "    'auth:admin-api',\n    'api.response',",
     "api-response-not-first"),
    ("группа маршрутов без api.response", "Entrypoints/Integration/Billing/routes.php", "    'api.response',\n", "", "api-response-not-first"),
    ("маршрут вне группы без api.response", "Entrypoints/Integration/Billing/routes.php", "Route::middleware([",
     "Route::get('ping', [TariffController::class, 'view']);\n\nRoute::middleware([", "api-response-not-first"),
    ("whereNumber на id в пути", "Entrypoints/Admin/Billing/routes.php", "->name('tariffs.view');", "->whereNumber('id')->name('tariffs.view');", "route-id-constrained"),
    ("where на id в пути", "Entrypoints/Integration/Billing/routes.php", "->name('parking-tariffs.index');", "->where('id', '[0-9]+')->name('parking-tariffs.index');",
     "route-id-constrained"),
    ("Route::pattern для id", "Entrypoints/Integration/Billing/routes.php", "Route::middleware([", "Route::pattern('id', '[0-9]+');\n\nRoute::middleware([", "route-id-constrained"),
    ("нет 403 у маршрута под permission:", "Entrypoints/Admin/Billing/Tariffs/Http/Controllers/TariffController.php", "response: 403", "response: 422", "controller-missing-swagger"),
    ("разрешено: whereNumber на параметре не id", "Entrypoints/Admin/Billing/routes.php", "->name('tariffs.view');", "->whereNumber('version')->name('tariffs.view');", None),
    ("разрешено: эталон", "Entrypoints/Admin/Billing/routes.php", "", "", None),
]

# --base: находка только на добавленных строках (было — в коммите, стало — в рабочем дереве)
OLD_ID = ("->name('tariffs.view');", "->whereNumber('id')->name('tariffs.view');")
RENAME = ("->name('parking-tariffs.index');", "->name('parking-tariffs.list');")
GROUP_PERMISSION = ("    'verify.signature',\n", "    'verify.signature',\n    'permission:parking.integration.tariffs.view',\n")
ROUTE_BASE_CASES = [  # (имя, файл в parking_app, было: (что заменить, на что), стало: (что заменить, на что), ожидаемый тег или None)
    ("--base: новый whereNumber на id", "Entrypoints/Integration/Billing/routes.php", ("", ""), OLD_ID, "route-id-constrained"),
    ("разрешено: --base: старый whereNumber в изменённом routes.php", "Entrypoints/Integration/Billing/routes.php", OLD_ID, RENAME, None),
    ("--base: permission: добавлен в существующую группу", "Entrypoints/Integration/Billing/routes.php", ("", ""), GROUP_PERMISSION,
     "controller-missing-swagger"),
]


def git(cwd, *args):
    subprocess.run(["git", "-c", "user.name=proof", "-c", "user.email=proof@example.com", *args], cwd=cwd,
                   check=True, capture_output=True)


def route_base_cases() -> int:
    fails = 0
    for name, path, before, after, expect in ROUTE_BASE_CASES:
        with tempfile.TemporaryDirectory() as tmp:
            sub = Path(tmp) / "parking_app"
            shutil.copytree(EXAMPLE, sub)
            f = sub / path
            stale = [old for old, _ in (before, after) if old and old not in f.read_text(encoding="utf-8")]
            if stale:
                print(f"MISS  {name} (в эталоне нет «{stale[0]}» — случай устарел)")
                fails += 1
                continue
            f.write_text(f.read_text(encoding="utf-8").replace(*before, 1), encoding="utf-8")
            git(tmp, "init", "-q")
            git(tmp, "add", "-A")
            git(tmp, "commit", "-q", "-m", "base")
            f.write_text(f.read_text(encoding="utf-8").replace(*after, 1), encoding="utf-8")
            found, _ = php_routes.check(sub.resolve(), "App\\ParkingApp")
            found = php_routes.touched(found, sub.resolve(), "HEAD")  # None — git не выполнился: случай не доказан
            msgs = " ".join(m for _, _, m, _ in found or [])
            ok = found is not None and ((f"({expect})" in msgs) if expect else not found)
            fails += not ok
            print(("OK    " if ok else "MISS  ") + name + ("" if ok else "\n" + msgs))
    return fails


def route_cases() -> int:
    fails = 0
    for name, path, old, new, expect in ROUTE_CASES:
        with tempfile.TemporaryDirectory() as tmp:
            sub = Path(tmp) / "parking_app"
            shutil.copytree(EXAMPLE, sub)
            f = sub / path
            text = f.read_text(encoding="utf-8")
            if old and old not in text:
                print(f"MISS  {name} (в эталоне нет «{old}» — случай устарел)")
                fails += 1
                continue
            f.write_text(text.replace(old, new, 1) if old else text, encoding="utf-8")
            found, _ = php_routes.check(sub, "App\\ParkingApp")
            msgs = " ".join(m for _, _, m, _ in found)
            ok = (f"({expect})" in msgs) if expect else not found
            fails += not ok
            print(("OK    " if ok else "MISS  ") + name)
    return fails


def large_diff() -> int:
    """lint.py php --base: тысячи изменённых файлов — в parameters.paths конфига, команда от их числа не растёт
    (иначе `docker exec` падает «argument list too long»); без --base в конфиге — весь подпроект."""
    import lint
    files = [f"/app/parking_app/Domains/M{i}/ZzV{i}.php" for i in range(3000)]
    neon, cmd = lint.phpstan_run("/app", "parking_app", "/tmp/df-lint/w", files)
    neon_all, cmd_all = lint.phpstan_run("/app", "parking_app", "/tmp/df-lint/w", [])
    listed = [l.strip()[3:-1] for l in neon.splitlines() if l.startswith("        - '")]
    ok = cmd == cmd_all and len(cmd) < 300 and listed == files and "        - /app/parking_app\n" in neon_all
    print(("OK    " if ok else "MISS  ") + "--base: 3000 изменённых файлов — в конфиге, не в командной строке")
    return not ok


def sh(cmd, stdin=None):
    return subprocess.run(["docker", "exec", "-i", CONTAINER, "sh", "-c", cmd], input=stdin, capture_output=True, text=True)


def main():
    fails = 0
    for name, path, code, expect in CASES:
        full = f"{WORKDIR}/parking_app/{path}"
        sh(f"cat > {full}", "<?php\n\ndeclare(strict_types=1);\n\n" + code + "\n")
        out = sh(f"cd {WORKDIR} && php -d memory_limit=2G vendor/bin/phpstan analyse --no-progress --error-format=json parking_app/{path} 2>/dev/null")
        text = out.stdout  # json: путь файла, сообщение и identifier (phpat.test…, disallowed.…)
        fname = "/" + Path(path).name + '"'  # ключ файла в json — его путь
        caught = fname in text and (expect or "") in text
        ran = '"totals"' in text  # phpstan отработал, а не упал: иначе «разрешено» прошло бы впустую
        ok = caught if expect else (ran and fname not in text)
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        sh(f"rm -f {full}")
    fails += route_cases()
    fails += route_base_cases()
    fails += large_diff()
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
