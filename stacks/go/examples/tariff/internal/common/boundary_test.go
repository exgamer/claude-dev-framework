package common_test

import (
	"go/parser"
	"go/token"
	"os"
	"path/filepath"
	"slices"
	"strconv"
	"strings"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

// commonImportPath Пакеты самого Common — единственный разрешённый импорт не из стандартной библиотеки
const commonImportPath = "example.com/parking-service/internal/common"

// ioImports Стандартная библиотека с вводом-выводом и контекстом запроса: в словаре не нужна
var ioImports = []string{`context`, `database/sql`, `net/http`, `os`}

// TestCommon_ImportsOnlyStdlibAndCommon Граница Common: код словаря импортирует только стандартную библиотеку без I/O и сам Common.
// Тесты Common не проверяются: им нужны testify и go/parser
func TestCommon_ImportsOnlyStdlibAndCommon(t *testing.T) {
	violations, files, err := forbiddenImports(".")
	require.NoError(t, err)

	assert.NotZero(t, files, "файлы Common не найдены — тест ничего не проверил")
	assert.Empty(t, violations)
}

// TestCommon_BoundaryCatchesForbiddenImport Проверка ловит импорт домена, фреймворка и I/O; образец лежит в testdata, вне кода Common
func TestCommon_BoundaryCatchesForbiddenImport(t *testing.T) {
	source, err := os.ReadFile(filepath.Join("testdata", "forbidden_imports.go.txt"))
	require.NoError(t, err)

	dir := t.TempDir()
	require.NoError(t, os.WriteFile(filepath.Join(dir, "bad.go"), source, 0o600))

	violations, _, err := forbiddenImports(dir)
	require.NoError(t, err)

	// Импорт пакета Common и strings разрешён; I/O стандартной библиотеки, фреймворк и домен — нет
	assert.Len(t, violations, 3)
}

// forbiddenImports Импорты не из стандартной библиотеки (первый элемент пути без точки) и не из Common, а также I/O стандартной библиотеки
func forbiddenImports(root string) ([]string, int, error) {
	var violations []string
	files := 0

	err := filepath.WalkDir(root, func(path string, entry os.DirEntry, walkErr error) error {
		if walkErr != nil {
			return walkErr
		}

		if entry.IsDir() || !strings.HasSuffix(path, ".go") || strings.HasSuffix(path, "_test.go") {
			return nil
		}

		file, parseErr := parser.ParseFile(token.NewFileSet(), path, nil, parser.ImportsOnly)
		if parseErr != nil {
			return parseErr
		}

		files++
		relative, _ := filepath.Rel(root, path)

		for _, spec := range file.Imports {
			importPath, _ := strconv.Unquote(spec.Path.Value)

			if !isAllowedImport(importPath) {
				violations = append(violations, relative+": "+importPath)
			}
		}

		return nil
	})

	return violations, files, err
}

func isAllowedImport(importPath string) bool {
	if importPath == commonImportPath || strings.HasPrefix(importPath, commonImportPath+"/") {
		return true
	}

	isStdlib := !strings.Contains(strings.Split(importPath, "/")[0], ".")

	return isStdlib && !slices.Contains(ioImports, importPath)
}
