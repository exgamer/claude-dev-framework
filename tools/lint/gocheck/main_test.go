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
