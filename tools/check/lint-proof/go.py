"""Доказательство для правил `stacks/go/examples/tariff/.golangci.yml`: каждое ловит заведомое нарушение.

Запуск из корня репозитория: python3 tools/check/lint-proof/go.py stacks/go/examples/tariff
Код возврата: 0 — все нарушения пойманы (линтером или сборкой) и чистый эталон даёт 0 замечаний.
Работает на временной копии эталона; репозиторий не меняется. Новое правило в конфиге — новый случай в CASES
(`core/rules-evolution.md`, «Статика первой»: тег отдаётся инструменту только после такой проверки).
"""
import contextlib, io, os, shutil, subprocess, sys, tempfile
from pathlib import Path
SRC = sys.argv[1]
M = "example.com/parking-service"
# Случаи, для которых конфиг собирается заново на копии с файлом случая: правило появляется, только когда есть каталог
# (`common` домена в эталоне нет — lint.py на проекте создаёт `{домен}-common-no-other-modules` по каталогам)
RERENDER = {"common домена→модуль домена"}
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
 ("модуль→соседний модуль домена", "internal/domains/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/domains/billing/outbox_tariff_event"\n', "billing-tariff-no-other-modules"),
 ("модуль→соседний модуль домена (identity)", "internal/domains/identity/admin/zz_v.go", f'package admin\nimport _ "{M}/internal/domains/identity/partner"\n', "identity-admin-no-other-modules"),
 ("common домена→модуль домена", "internal/domains/billing/common/zz_v.go", f'package common\nimport _ "{M}/internal/domains/billing/tariff"\n', "billing-common-no-other-modules"),
 ("pkg→internal", "pkg/zzthing/zz_v.go", f'package zzthing\nimport _ "{M}/internal/common"\n', "pkg-no-internal"),
 ("workflow→workflow", "internal/workflows/billing/zzother/zz_v.go", f'package zzother\nimport _ "{M}/internal/workflows/billing/tariff"\n', "workflow-no-workflow"),
 ("workflow→infrastructure", "internal/workflows/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/infrastructure/postgres/billing/tariff"\n', "workflow-no-workflow"),
 ("entrypoint→infrastructure", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/infrastructure/postgres/billing/tariff"\n', "entrypoint-no-infra"),
 ("entrypoint→app", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", f'package tariff\nimport _ "{M}/internal/app"\n', "entrypoint-no-infra"),
 ("common→internal", "internal/common/zz_v.go", f'package common\nimport _ "{M}/internal/entrypoints/system/http/healthcheck"\n', "common-no-internal"),
 ("context.Background в домене", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport "context"\nfunc zzF() context.Context {\n\treturn context.Background()\n}\n', "forbidigo"),
 ("разрешено: context.Background в e2e-обвязке tests/", "tests/harness/zz_v.go", 'package harness\nimport "context"\nfunc ZzF() context.Context {\n\treturn context.Background()\n}\n', None),
 ("разрешено: context.Background в internal/testsupport", "internal/testsupport/zzfakes/zz_v.go", 'package zzfakes\nimport "context"\nfunc ZzF() context.Context {\n\treturn context.Background()\n}\n', None),
 ("fmt.Println в домене", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport "fmt"\nfunc zzF() {\n\tfmt.Println("zz")\n}\n', "отладочный вывод"),
 ("println в точке запуска", "internal/app/bootstrap/billing/tariff/zz_v.go", 'package tariff\nfunc zzF() {\n\tprintln("zz")\n}\n', "отладочный вывод"),
 ("fmt.Printf в тесте", "internal/domains/billing/tariff/zz_v_test.go", 'package tariff\nimport (\n\t"fmt"\n\t"testing"\n)\nfunc TestZz(t *testing.T) {\n\tfmt.Printf("zz %d", 1)\n}\n', "отладочный вывод"),
 ("разрешено: fmt.Sprintf и context.Background в точке запуска", "internal/app/bootstrap/billing/tariff/zz_v.go", 'package tariff\nimport (\n\t"context"\n\t"fmt"\n)\nfunc zzF() (context.Context, string) {\n\treturn context.Background(), fmt.Sprintf("zz %d", 1)\n}\n', None),
 ("ошибка через _", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport "strconv"\nfunc zzF() int {\n\tn, _ := strconv.Atoi("1")\n\n\treturn n\n}\n', "errcheck"),
 ("ошибка не проверена", "internal/infrastructure/postgres/billing/tariff/zz_v.go", 'package tariff\nimport "os"\nfunc zzF() {\n\tos.Chdir("zz")\n}\n', "errcheck"),
 ("разрешено: defer f.Close() после чтения", "internal/infrastructure/postgres/billing/tariff/zz_v.go", 'package tariff\nimport (\n\t"io"\n\t"os"\n)\nfunc zzF() ([]byte, error) {\n\tf, err := os.Open("zz")\n\tif err != nil {\n\t\treturn nil, err\n\t}\n\tdefer f.Close()\n\n\treturn io.ReadAll(f)\n}\n', None),
 ("разрешено: errorreporter.CaptureSoft отдельной строкой", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport (\n\t"context"\n\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"\n)\nfunc zzF(ctx context.Context, err error) {\n\terrorreporter.CaptureSoft(ctx, err, nil)\n\terrorreporter.CaptureError(ctx, err, nil)\n}\n', None),
 ("импорты не по группам", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport (\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"\n\t"context"\n)\nfunc zzF(ctx context.Context, err error) {\n\terrorreporter.CaptureSoft(ctx, err, nil)\n}\n', "gci"),
 ("разрешено: импорты тремя группами", "internal/domains/billing/tariff/zz_v.go", f'package tariff\nimport (\n\t"context"\n\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/errorreporter"\n\n\t"{M}/internal/common"\n)\nvar _ common.Currency\nfunc zzF(ctx context.Context, err error) {{\n\terrorreporter.CaptureSoft(ctx, err, nil)\n}}\n', None),
 ("нет пустой строки перед return", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc zzF(a int) int {\n\tb := a + 1\n\treturn b\n}\n', "nlreturn"),
 ("ctx не первым", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nimport "context"\nfunc ZzF(a int, ctx context.Context) error {\n\t_ = ctx\n\n\treturn nil\n}\n', "context-as-argument"),
 ("разрешено: internal/common импортирует свой подпакет", "internal/common/zzsub/zz_v.go", 'package zzsub\n', None),
 ("разрешено: internal/common импортирует свой подпакет (импорт)", "internal/common/zz_v.go", f'package common\nimport _ "{M}/internal/common/zzsub"\n', None),
 ("разрешено: константа ALL_CAPS", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nconst ZZ_LIMIT = 1\n', None),
 ("разрешено: имя с подчёркиванием", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc zzF() int {\n\tmax_value := 1\n\n\treturn max_value\n}\n', None),
 ("разрешено: внешний тест workflow импортирует workflow", "internal/workflows/billing/tariff/zz_v_test.go", f'package tariff_test\nimport _ "{M}/internal/workflows/billing/tariff"\n', None),
 ("Url вместо URL", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc ZzF() string {\n\tbaseUrl := "x"\n\n\treturn baseUrl\n}\n', "var-naming"),
 ("SetTimeout пагинатора с time.Second", "internal/infrastructure/postgres/billing/tariff/zz_v.go", 'package tariff\nimport (\n\t"time"\n\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/helpers"\n)\nfunc zzF(h *helpers.GormPaginatedHelper[int]) {\n\th.SetTimeout(10 * time.Second)\n}\n', "paginator-timeout-units"),
 ("разрешено: SetTimeout пагинатора числом", "internal/infrastructure/postgres/billing/tariff/zz_v.go", 'package tariff\nimport "git.mpinnovations.kz/mps/go-packages/gosdk-db-core/pkg/query/helpers"\nfunc zzF(h *helpers.GormPaginatedHelper[int]) {\n\th.SetTimeout(10)\n}\n', None),
 ("validate без binding в запросе", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", 'package tariff\ntype zzRequest struct {\n\tName string `json:"name" validate:"required,max=255"`\n}\n\nvar _ = zzRequest{}\n', "validate-tag-ignored"),
 ("validate расходится с binding", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", 'package tariff\ntype zzRequest struct {\n\tName string `json:"name" binding:"required" validate:"required,max=255"`\n}\n\nvar _ = zzRequest{}\n', "validate-tag-ignored"),
 ("разрешено: validate совпадает с binding", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", 'package tariff\ntype zzRequest struct {\n\tName string `json:"name" binding:"required" validate:"required"`\n}\n\nvar _ = zzRequest{}\n', None),
 ("файл ports.go", "internal/domains/billing/tariff/ports.go", 'package tariff\n', "interface-file-generic-name"),
 ("файл utils.go", "internal/domains/billing/tariff/utils.go", 'package tariff\n', "file-named-by-abstraction"),
 ("файл constants.go", "internal/domains/billing/tariff/constants.go", 'package tariff\n', "file-named-by-abstraction"),
 ("файл helpers.go", "internal/workflows/billing/tariff/helpers.go", 'package tariff\n', "file-named-by-abstraction"),
 ("разрешено: файл enum.go", "internal/domains/billing/tariff/enum.go", 'package tariff\n', None),
 ("константа в файле сервиса", "internal/domains/billing/zzother/service.go", 'package zzother\n\nconst zzLimit = 1\n\nvar _ = zzLimit\n', "constants-in-service-file"),
 ("константа в файле *_service.go", "internal/domains/billing/tariff/zz_v_service.go", 'package tariff\n\nconst zzLimit = 1\n\nvar _ = zzLimit\n', "constants-in-service-file"),
 ("константа в файле Command", "internal/domains/billing/tariff/zz_v_command.go", 'package tariff\n\nconst zzLimit = 1\n\nvar _ = zzLimit\n', "constants-in-service-file"),
 ("константа в файле Query", "internal/domains/billing/tariff/zz_v_query.go", 'package tariff\n\nconst (\n\tzzA = 1\n\tzzB = 2\n)\n\nvar _ = zzA + zzB\n', "constants-in-service-file"),
 ("разрешено: константа в файле валидатора", "internal/domains/billing/tariff/zz_v_validator.go", 'package tariff\n\nconst zzMax = 1\n\nvar _ = zzMax\n', None),
 ("разрешено: константа в консольном *_command.go точки входа", "internal/entrypoints/console/billing/tariff/zz_v_command.go", 'package tariff\n\nconst zzLimit = 1\n\nvar _ = zzLimit\n', None),
 ("ссылка на пункт design в комментарии", "internal/domains/billing/tariff/zz_v.go", 'package tariff\n\n// R15: тариф без цены не сохраняется\nvar zzA = 1\n', "task-artifact-ref-in-code"),
 ("ссылка на раздел design", "internal/domains/billing/tariff/zz_v.go", 'package tariff\n\n// Порядок проверок — design §4.2\nvar zzA = 1\n', "task-artifact-ref-in-code"),
 ("ссылка на артефакт в имени кейса теста", "internal/domains/billing/tariff/zz_v_test.go", 'package tariff\nimport "testing"\nfunc TestZz(t *testing.T) {\n\tt.Run("T3: пустой список", func(t *testing.T) {})\n}\n', "task-artifact-ref-in-code"),
 ("разрешено: коды в данных теста", "internal/domains/billing/tariff/zz_v_test.go", 'package tariff\nimport "testing"\n\n// Ячейка и модель — данные, не ссылки; обёртка над `Manager2[R1, R2]`\nfunc TestZz(t *testing.T) {\n\tcell, model, body := "A1", "T800", `{"F1":1}`\n\t_, _, _ = cell, model, body\n}\n', None),
 ("разрешено: тест домена импортирует gorm", "internal/domains/billing/tariff/zz_v_test.go", 'package tariff\nimport (\n\t"testing"\n\n\t"gorm.io/gorm"\n)\nfunc TestZz(t *testing.T) {\n\tvar _ *gorm.DB\n}\n', None),
 ("контейнер DI в routes.go точки входа", "internal/entrypoints/admin/http/billing/tariff/zz_v.go", 'package tariff\nimport (\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"\n\t"github.com/gin-gonic/gin"\n)\n// ZzRouter роутер из контейнера\nfunc ZzRouter(a *app.App) (*gin.Engine, error) {\n\treturn di.GetRouter(a.Container)\n}\n', "di-resolve-in-business-code"),
 ("контейнер DI в фабрике bootstrap", "internal/app/bootstrap/billing/tariff/zz_v.go", 'package tariff\nimport (\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"\n\t"github.com/gin-gonic/gin"\n)\n// ZzRouter роутер из контейнера\nfunc ZzRouter(a *app.App) (*gin.Engine, error) {\n\treturn di.GetRouter(a.Container)\n}\n', "di-resolve-in-business-code"),
 ("разрешено: контейнер DI в module.go bootstrap", "internal/app/bootstrap/billing/zzother/module.go", 'package zzother\nimport (\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app"\n\t"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di"\n\t"github.com/gin-gonic/gin"\n)\n// ZzRouter роутер из контейнера\nfunc ZzRouter(a *app.App) (*gin.Engine, error) {\n\treturn di.GetRouter(a.Container)\n}\n', None),
 ("Id вместо ID", "internal/domains/billing/tariff/zz_v.go", 'package tariff\nfunc ZzF() int {\n\tparkingId := 1\n\n\treturn parkingId\n}\n', "var-naming"),
]
# GOSUMDB не выключается: при локальном Go ниже версии из go.mod эталона go переключается на тулчейн из кэша и сверяет его по sumdb
env = dict(os.environ, GOFLAGS="-mod=mod", GOPROXY="off", GONOSUMDB="git.mpinnovations.kz", GOPRIVATE="", GONOPROXY="")
sys.path.insert(0, os.path.join(os.path.dirname(os.path.abspath(__file__)), "../../lint"))
import lint  # noqa: E402  — анализатор фреймворка gocheck: правила без линтера в golangci

GOCHECK = str(lint.gocheck_binary())


def run_all(cwd):
    """golangci-lint + gocheck, как lint.py go: последняя строка — итог golangci («0 issues.»), если gocheck чист."""
    g = subprocess.run(["golangci-lint", "run", "./..."], cwd=cwd, env=env, capture_output=True, text=True)
    c = subprocess.run([GOCHECK, cwd], capture_output=True, text=True)
    own = [l for l in c.stdout.splitlines() if not l.startswith("gocheck:")]
    return "\n".join(own) + "\n" + g.stdout + g.stderr
work = tempfile.mkdtemp(prefix="lintproof-")
dst = os.path.join(work, "tariff")
shutil.copytree(SRC, dst)
fails = 0
for name, path, code, expect in CASES:
    full = os.path.join(dst, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    open(full, "w").write(code)
    subprocess.run(["gofmt", "-w", full], check=True)  # gci проверяет и форматирование gofmt — случай про своё правило
    cfg = os.path.join(dst, ".golangci.yml")
    original = open(cfg).read()
    if name in RERENDER:
        open(cfg, "w").write(lint.render_go(Path(dst)))
    text = run_all(dst)
    open(cfg, "w").write(original)
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
    ok = expect in text and os.path.basename(path) in text
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
clean = run_all(dst)
last = clean.strip().splitlines()[-1] if clean.startswith("\n") else "gocheck: " + clean.splitlines()[0]
print("чистый эталон:", last)
fails += last != "0 issues."
# --base с тысячами изменённых файлов (~1,3 МБ путей — больше предела командной строки): список уходит в gocheck через stdin
for args in (["init", "-q"], ["add", "-A"], ["-c", "user.name=proof", "-c", "user.email=proof@example.com", "commit", "-q", "-m", "base"]):
    subprocess.run(["git", *args], cwd=dst, check=True, capture_output=True)
big = os.path.join(dst, "internal/common", "zzbig" + "d" * 200, "e" * 200)
os.makedirs(big)
for i in range(3000):
    open(os.path.join(big, f"zz_{i}.go"), "w").write("package " + "e" * 200 + "\n")
out = io.StringIO()
with contextlib.redirect_stdout(out):
    code = lint.run_gocheck(Path(dst), "HEAD")
ok = code == 0 and "gocheck: файлов: 3000; находок: 0" in out.getvalue()
fails += not ok
print(("OK    " if ok else "MISS  ") + "--base: 3000 изменённых файлов (длинные пути) — через stdin" + ("" if ok else "\n" + out.getvalue()[-500:]))
shutil.rmtree(work)
sys.exit(1 if fails else 0)
