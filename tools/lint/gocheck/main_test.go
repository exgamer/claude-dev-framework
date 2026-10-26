package main

import (
	"go/token"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

// check Находки gocheck по одному файлу во временном проекте
func check(t *testing.T, rel, code string) []string {
	t.Helper()
	root := t.TempDir()
	full := filepath.Join(root, rel)
	if err := os.MkdirAll(filepath.Dir(full), 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(full, []byte(code), 0o600); err != nil {
		t.Fatal(err)
	}
	var msgs []string
	for _, f := range checkFile(token.NewFileSet(), root, rel) {
		msgs = append(msgs, f.msg)
	}

	return msgs
}

func TestFileNamedByAbstraction(t *testing.T) {
	for _, name := range []string{"types.go", "const.go", "constants.go", "consts.go", "utils.go", "helpers.go", "common.go", "misc.go"} {
		msgs := check(t, "internal/domains/billing/tariff/"+name, "package tariff\n")
		if len(msgs) != 1 || !strings.Contains(msgs[0], "(file-named-by-abstraction)") {
			t.Errorf("%s: ждали file-named-by-abstraction, получили %v", name, msgs)
		}
	}
	for _, name := range []string{"enum.go", "dto.go", "entity.go", "tariff_enum.go"} {
		if msgs := check(t, "internal/domains/billing/tariff/"+name, "package tariff\n"); len(msgs) != 0 {
			t.Errorf("%s: не абстракция, получили %v", name, msgs)
		}
	}
}

func TestTaskArtifactRef(t *testing.T) {
	found := []struct{ name, code string }{
		{"ID в начале комментария", "package x\n\n// R15: тариф без цены не сохраняется\nvar a = 1\n"},
		{"ID в скобках в комментарии", "package x\n\n// Проверка лимита (AC2)\nvar a = 1\n"},
		{"ID с буквой", "package x\n\n// см. T3a, Th2\nvar a = 1\n"},
		{"раздел design", "package x\n\n// по design §4.2\nvar a = 1\n"},
		{"путь docs/tasks", "package x\n\nvar a = \"docs/tasks/AINA-1/design.md\"\n"},
		{"имя кейса теста", "package x\n\nimport \"testing\"\n\nfunc TestX(t *testing.T) {\n\tt.Run(\"T3: пустой список\", func(t *testing.T) {})\n}\n"},
		{"имя кейса с тире", "package x\n\nvar a = \"RV2 — повтор\"\n"},
		{"ID в скобках в строке", "package x\n\nvar a = \"лимит превышен (AC2)\"\n"},
		{"блочный комментарий", "package x\n\n/* Q4: решено */\nvar a = 1\n"},
	}
	for _, c := range found {
		msgs := check(t, "internal/x/x.go", c.code)
		if len(msgs) != 1 || !strings.Contains(msgs[0], "(task-artifact-ref-in-code)") {
			t.Errorf("%s: ждали task-artifact-ref-in-code, получили %v", c.name, msgs)
		}
	}
	clean := []struct{ name, code string }{
		{"обычный комментарий", "package x\n\n// Тариф без цены не сохраняется\nvar a = 1\n"},
		{"код в обратных кавычках", "package x\n\n// Обёртка над `Manager2[R1, R2]`\nvar a = 1\n"},
		{"параметры типа", "package x\n\ntype M[R1, R2 any] struct{}\n"},
		{"строка из одного кода", "package x\n\nvar cell, model = \"A1\", \"T800\"\n"},
		{"ключ JSON", "package x\n\nvar a = `{\"F1\":1,\"R2\":\"x\"}`\n"},
		{"ID в тексте строки", "package x\n\nvar a = \"SubTest A1 и модель P2 включены\"\n"},
		{"имя алгоритма", "package x\n\n// подпись AWS4-ECDSA-P256-SHA256, адрес %F0%9F\nvar a = 1\n"},
		{"путь поля", "package x\n\n// поле V.F2 ответа\nvar a = 1\n"},
		{"без цифр", "package x\n\n// P2P-перевод, T-шка, AC-адаптер\nvar a = 1\n"},
		{"общепринятое", "package x\n\n// формат A4, кеш L2\nvar a = 1\n"},
		{"тег поля", "package x\n\ntype R struct {\n\tA string `json:\"T3: x\"`\n}\n"},
		{"импорт", "package x\n\nimport _ \"example.com/docs/tasks/x\"\n"},
		{"сырая строка с меткой", "package x\n\nvar q = `T3: SELECT 1`\n"},
	}
	for _, c := range clean {
		if msgs := check(t, "internal/x/x.go", c.code); len(msgs) != 0 {
			t.Errorf("%s: ждали 0 находок, получили %v", c.name, msgs)
		}
	}
}

func TestDIOutsideAssembly(t *testing.T) {
	const routes = "package tariff\n\nimport (\n\t\"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app\"\n\t\"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/di\"\n)\n\nfunc SetRoutes(a *app.App) {\n\t_, _ = di.GetRouter(a.Container)\n}\n"
	const resolve = "package tariff\n\nimport (\n\t\"github.com/exgamer/gosdk-core/pkg/app\"\n\t\"github.com/exgamer/gosdk-core/pkg/di\"\n)\n\nfunc newServices(a *app.App) {\n\t_, _ = di.Resolve[*int](a.Container)\n}\n"
	const field = "package tariff\n\nimport \"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app\"\n\nfunc newHandler(a *app.App) any {\n\treturn a.Container\n}\n"
	found := []struct {
		name, rel, code string
		n               int
	}{
		{"routes.go точки входа", "internal/entrypoints/admin/http/billing/tariff/routes.go", routes, 2},
		{"фабрика bootstrap", "internal/app/bootstrap/billing/tariff/services_factory.go", resolve, 2},
		{"контейнер полем в хендлере", "internal/entrypoints/admin/http/middleware/jwt_auth.go", field, 1},
		{"pkg/", "pkg/zz/zz.go", routes, 2},
	}
	for _, c := range found {
		msgs := check(t, c.rel, c.code)
		if len(msgs) != c.n || !strings.Contains(msgs[0], "(di-resolve-in-business-code)") {
			t.Errorf("%s: ждали %d di-resolve-in-business-code, получили %v", c.name, c.n, msgs)
		}
	}
	for _, rel := range []string{
		"internal/app/app.go", "internal/console/app.go", "internal/app/kernel/zz/kernel.go",
		"internal/app/bootstrap/billing/tariff/module.go", "internal/app/bootstrap/tariff/module.go",
		"internal/console/bootstrap/billing/outbox_tariff_event/module.go",
		"internal/entrypoints/admin/http/billing/tariff/routes_test.go", "internal/testsupport/zz/app.go", "tests/harness/app.go",
	} {
		if msgs := check(t, rel, routes); len(msgs) != 0 {
			t.Errorf("%s: сборка или тест, ждали 0 находок, получили %v", rel, msgs)
		}
	}
	other := "package docker\n\ntype Spec struct{ Container string }\n\nfunc name(s Spec) string {\n\treturn s.Container\n}\n"
	if msgs := check(t, "internal/infrastructure/docker/spec.go", other); len(msgs) != 0 {
		t.Errorf("поле Container не из SDK: ждали 0 находок, получили %v", msgs)
	}
	ownWithApp := "package tariff\n\nimport \"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app\"\n\ntype Spec struct{ Container string }\n\nfunc name(_ *app.App, s Spec) string {\n\treturn s.Container\n}\n"
	if msgs := check(t, "internal/entrypoints/admin/http/billing/tariff/spec.go", ownWithApp); len(msgs) != 0 {
		t.Errorf("своё поле Container в файле с pkg/app: ждали 0 находок, получили %v", msgs)
	}
	viaField := "package tariff\n\nimport sdkapp \"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/app\"\n\ntype holder struct {\n\tapp *sdkapp.App\n}\n\ntype wrapper struct {\n\t*sdkapp.App\n}\n\nfunc (h holder) c(w wrapper) (any, any) {\n\treturn h.app.Container, w.App.Container\n}\n"
	if msgs := check(t, "internal/app/bootstrap/billing/tariff/handlers_factory.go", viaField); len(msgs) != 2 {
		t.Errorf("контейнер через поле и встроенный *app.App: ждали 2 находки, получили %v", msgs)
	}
}
