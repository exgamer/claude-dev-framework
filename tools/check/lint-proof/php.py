#!/usr/bin/env python3
"""Доказательство для правил PHP-эталона (`tests/Architecture/LayersArchitecture.php`, `phpstan-rules.neon`):
каждое ловит заведомое нарушение, разрешённое (Eloquent-модель в домене, P-4) — не ловит.

Запуск из корня репозитория после `tools/php-check/run.sh` (стенд с vendor уже в контейнере):
    python3 tools/check/lint-proof/php.py [PHP_CONTAINER]
Нарушение кладётся файлом в рабочую копию стенда в контейнере, анализируется только этот файл, файл удаляется.
"""
import subprocess
import sys

CONTAINER = sys.argv[1] if len(sys.argv) > 1 else "php-8.5"
WORKDIR = "/tmp/df-php-check"
R = "App\\ParkingApp"
CASES = [  # (имя, путь в parking_app, код класса, ожидаемый идентификатор или None — нарушения быть не должно)
    ("домен → Infrastructure", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function __construct(private readonly \\{R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Repositories\\TariffRepository $r) {{}} }}",
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
    ("app() в бизнес-коде", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(): void {{ app('cache'); }} }}",
     "app()"),
    ("разрешено: фасад DB в репозитории Infrastructure", "Infrastructure/Postgres/Billing/Tariffs/Repositories/ZzV.php",
     f"namespace {R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Repositories;\nfinal class ZzV {{ public function f(): void {{ \\Illuminate\\Support\\Facades\\DB::raw('1'); }} }}",
     None),
    ("разрешено: фасад DB в миграции", "Infrastructure/Postgres/Billing/Providers/database/migrations/zz_v.php",
     "return new class () { public function up(): void { \\Illuminate\\Support\\Facades\\DB::statement('select 1'); } };",
     None),
    ("разрешено: фасад DB в тестах", "../tests/Unit/ZzV.php",
     "namespace Tests\\Unit;\nfinal class ZzV { public function f(): void { \\Illuminate\\Support\\Facades\\DB::table('x'); } }",
     None),
    ("разрешено: модель Eloquent в домене (P-4)", "Domains/Billing/Modules/Tariffs/ZzV.php",
     f"namespace {R}\\Domains\\Billing\\Modules\\Tariffs;\nfinal class ZzV {{ public function f(\\{R}\\Infrastructure\\Postgres\\Billing\\Tariffs\\Models\\Tariff $t): void {{}} }}",
     None),
]


def sh(cmd, stdin=None):
    return subprocess.run(["docker", "exec", "-i", CONTAINER, "sh", "-c", cmd], input=stdin, capture_output=True, text=True)


def main():
    fails = 0
    for name, path, code, expect in CASES:
        full = f"{WORKDIR}/parking_app/{path}"
        sh(f"cat > {full}", "<?php\n\ndeclare(strict_types=1);\n\n" + code + "\n")
        out = sh(f"cd {WORKDIR} && php -d memory_limit=2G vendor/bin/phpstan analyse --no-progress --error-format=json parking_app/{path} 2>/dev/null")
        text = out.stdout  # json: сообщение и identifier (phpat.test…, disallowed.…)
        caught = "ZzV" in text and (expect or "") in text
        ran = '"totals"' in text  # phpstan отработал, а не упал: иначе «разрешено» прошло бы впустую
        ok = caught if expect else (ran and "ZzV" not in text and "zz_v" not in text)
        fails += not ok
        print(("OK    " if ok else "MISS  ") + name)
        sh(f"rm -f {full}")
    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
