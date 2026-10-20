"""Доказательство для правил `stacks/go/examples/tariff/.golangci.yml`: каждое ловит заведомое нарушение.

Запуск из корня репозитория: python3 tools/check/lint-proof/go.py stacks/go/examples/tariff
Код возврата: 0 — все нарушения пойманы (линтером или сборкой) и чистый эталон даёт 0 замечаний.
Работает на временной копии эталона; репозиторий не меняется. Новое правило в конфиге — новый случай в CASES
(`core/rules-evolution.md`, «Статика первой»: тег отдаётся инструменту только после такой проверки).
"""
import os, shutil, subprocess, sys, tempfile
SRC = sys.argv[1]
M = "example.com/parking-service"
CASES = [  # (имя, путь файла, код, что должно быть в выводе)
 ("domain→infrastructure", "internal/domains/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/infrastructure/jwt/identity/admin"\n', "domain-no-infra"),
 ("domain→entrypoints", "internal/domains/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/entrypoints/system/http/healthcheck"\n', "domain-no-infra"),
 ("domain→workflows", "internal/domains/identity/partner/zz_v.go", f'package partner\nimport _ "{M}/internal/workflows/billing/tariff"\n', "domain-no-infra"),
 ("domain→app", "internal/domains/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/app"\n', "domain-no-infra"),
 ("domain→gorm", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport _ "gorm.io/gorm"\n', "domain-no-infra"),
 ("domain→gin", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport _ "github.com/gin-gonic/gin"\n', "domain-no-infra"),
 ("domain→net/http", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport _ "net/http"\n', "domain-no-infra"),
 ("billing→identity", "internal/domains/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/domains/identity/admin"\n', "billing-no-other-domains"),
 ("identity→billing", "internal/domains/identity/admin/zz_v.go", f'package admin\nimport _ "{M}/internal/domains/billing/tariff"\n', "identity-no-other-domains"),
 ("handbook→billing", "internal/domains/handbook/parking/zz_v.go", f'package parking\nimport _ "{M}/internal/domains/billing/tariff"\n', "handbook-no-other-domains"),
 ("workflow→workflow", "internal/workflows/billing/zzother/zz_v.go", f'package zzother\nimport _ "{M}/internal/workflows/billing/tariff"\n', "workflow-no-workflow"),
 ("workflow→infrastructure", "internal/workflows/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/infrastructure/postgres/billing/tariff"\n', "workflow-no-workflow"),
 ("entrypoint→infrastructure", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/infrastructure/postgres/billing/tariff"\n', "entrypoint-no-infra"),
 ("entrypoint→app", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/app"\n', "entrypoint-no-infra"),
 ("common→internal", "internal/common/zz_v.go", f'package common\nimport _ "{M}/internal/entrypoints/system/http/healthcheck"\n', "common-no-internal"),
 ("context.Background в домене", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport "context"\nfunc zzF() context.Context {\n\treturn context.Background()\n}\n', "forbidigo"),
 ("нет пустой строки перед return", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc zzF(a int) int {\n\tb := a + 1\n\treturn b\n}\n', "nlreturn"),
 ("ctx не первым", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport "context"\nfunc ZzF(a int, ctx context.Context) error {\n\t_ = ctx\n\n\treturn nil\n}\n', "context-as-argument"),
 ("разрешено: internal/common импортирует свой подпакет", "internal/common/zzsub/zz_v.go", 'package zzsub\n', None),
 ("разрешено: internal/common импортирует свой подпакет (импорт)", "internal/common/zz_v.go", f'package common\nimport _ "{M}/internal/common/zzsub"\n', None),
 ("разрешено: константа ALL_CAPS", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nconst ZZ_LIMIT = 1\n', None),
 ("разрешено: имя с подчёркиванием", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc zzF() int {\n\tmax_value := 1\n\n\treturn max_value\n}\n', None),
 ("разрешено: внешний тест workflow импортирует workflow", "internal/workflows/billing/tariff/zz_v_test.go", f'package tariff_test\nimport _ "{M}/internal/workflows/billing/tariff"\n', None),
 ("Url вместо URL", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc ZzF() string {\n\tbaseUrl := "x"\n\n\treturn baseUrl\n}\n', "var-naming"),
 ("Id вместо ID", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc ZzF() int {\n\tparkingId := 1\n\n\treturn parkingId\n}\n', "var-naming"),
]
env = dict(os.environ, GOFLAGS="-mod=mod", GOPROXY="off", GOSUMDB="off", GOPRIVATE="", GONOPROXY="")
work = tempfile.mkdtemp(prefix="lintproof-")
dst = os.path.join(work, "tariff")
shutil.copytree(SRC, dst)
fails = 0
for name, path, code, expect in CASES:
    full = os.path.join(dst, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    open(full, "w").write(code)
    out = subprocess.run(["golangci-lint", "run", "./..."], cwd=dst, env=env, capture_output=True, text=True)
    text = out.stdout + out.stderr
    if expect is None:  # разрешённое правилами: замечаний быть не должно (zzsub создаётся предыдущим случаем)
        ok = "zz_v" not in text and text.strip().splitlines()[-1] == "0 issues."
        fails += not ok
        print(("OK    " if ok else "NOISE ") + name)
        if path.endswith("zzsub/zz_v.go"):
            continue
        os.remove(full)
        sub = os.path.join(dst, "internal/common/zzsub")
        if os.path.isdir(sub):
            shutil.rmtree(sub)
        continue
    ok = expect in text and "zz_v.go" in text
    # Цикл импорта — нарушение ловит сборка; только он, иная ошибка (опечатка в пути) — не «поймано»
    cycle = False
    if not ok:
        build = subprocess.run(["go", "build", "./..."], cwd=dst, env=env, capture_output=True, text=True)
        cycle = "import cycle not allowed" in build.stderr
    fails += not (ok or cycle)
    print(("OK    " if ok else "CYCLE " if cycle else "MISS  ") + name + ("  (не компилируется — ловит сборка)" if cycle and not ok else ""))
    os.remove(full)
    if not os.listdir(os.path.dirname(full)):
        os.rmdir(os.path.dirname(full))
clean = subprocess.run(["golangci-lint", "run", "./..."], cwd=dst, env=env, capture_output=True, text=True)
last = (clean.stdout + clean.stderr).strip().splitlines()[-1]
print("чистый эталон:", last)
fails += last != "0 issues."
shutil.rmtree(work)
sys.exit(1 if fails else 0)
