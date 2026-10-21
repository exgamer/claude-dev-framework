// Command gocheck — правила фреймворка для Go, которых нет в golangci-lint: разбор AST, только стандартная библиотека.
// Запускает tools/lint/lint.py go; вывод — как у golangci-lint: «файл:строка:колонка: сообщение (тег)».
//
//	gocheck <корень проекта> [файл.go ...]   — без файлов: все .go проекта (кроме vendor/, testdata/, скрытых папок)
//
// Код возврата: 0 — чисто, 1 — есть находки, 2 — ошибка запуска.
package main

import (
	"fmt"
	"go/ast"
	"go/parser"
	"go/token"
	"io/fs"
	"os"
	"path/filepath"
	"reflect"
	"sort"
	"strconv"
	"strings"
)

// Имена файлов — каталог тегов stacks/go/review-tags.md.
var genericInterfaceFiles = map[string]bool{"ports.go": true, "interfaces.go": true, "dependencies.go": true}
var abstractionFiles = map[string]bool{"types.go": true, "const.go": true, "utils.go": true}

// Единицы времени: SetTimeout пагинатора SDK принимает число секунд и сам умножает на time.Second.
var timeUnits = map[string]bool{"Nanosecond": true, "Microsecond": true, "Millisecond": true, "Second": true, "Minute": true, "Hour": true}

const paginatorPkg = "gosdk-db-core/pkg/query/helpers"

type finding struct {
	pos token.Position
	msg string
}

func main() {
	if len(os.Args) < 2 {
		fmt.Fprintln(os.Stderr, "использование: gocheck <корень проекта> [файл.go ...]")
		os.Exit(2)
	}
	root := os.Args[1]
	files := os.Args[2:]
	if len(files) == 0 {
		var err error
		if files, err = goFiles(root); err != nil {
			fmt.Fprintln(os.Stderr, err)
			os.Exit(2)
		}
	}
	var out []finding
	fset := token.NewFileSet()
	for _, rel := range files {
		out = append(out, checkFile(fset, root, rel)...)
	}
	sort.Slice(out, func(i, j int) bool {
		if out[i].pos.Filename != out[j].pos.Filename {
			return out[i].pos.Filename < out[j].pos.Filename
		}

		return out[i].pos.Line < out[j].pos.Line
	})
	for _, f := range out {
		fmt.Printf("%s:%d:%d: %s\n", f.pos.Filename, f.pos.Line, f.pos.Column, f.msg)
	}
	fmt.Printf("gocheck: файлов: %d; находок: %d\n", len(files), len(out))
	if len(out) > 0 {
		os.Exit(1)
	}
}

func goFiles(root string) ([]string, error) {
	var files []string
	err := filepath.WalkDir(root, func(path string, d fs.DirEntry, err error) error {
		if err != nil {
			return err
		}
		name := d.Name()
		if d.IsDir() {
			if path != root && (name == "vendor" || name == "testdata" || strings.HasPrefix(name, ".")) {
				return filepath.SkipDir
			}

			return nil
		}
		if strings.HasSuffix(name, ".go") {
			rel, _ := filepath.Rel(root, path)
			files = append(files, rel)
		}

		return nil
	})

	return files, err
}

func checkFile(fset *token.FileSet, root, rel string) []finding {
	var out []finding
	rel = filepath.ToSlash(rel)
	base := filepath.Base(rel)
	at := token.Position{Filename: rel, Line: 1, Column: 1}
	if genericInterfaceFiles[base] {
		out = append(out, finding{at, fmt.Sprintf("файл %s — общий термин, назвать по объявленному (repository.go, publisher.go; в модуле домена — файл группы) (interface-file-generic-name)", base)})
	}
	if abstractionFiles[base] {
		out = append(out, finding{at, fmt.Sprintf("файл %s назван по абстракции — по смыслу (enum.go модуля, dto.go, entity.go) (file-named-by-abstraction)", base)})
	}
	file, err := parser.ParseFile(fset, filepath.Join(root, rel), nil, parser.SkipObjectResolution)
	if err != nil {
		return out // синтаксис — дело компилятора и golangci-lint
	}
	pos := func(p token.Pos) token.Position {
		x := fset.Position(p)
		x.Filename = rel

		return x
	}
	timeName, paginator := "", false
	for _, imp := range file.Imports {
		path, _ := strconv.Unquote(imp.Path.Value)
		if path == "time" {
			timeName = "time"
			if imp.Name != nil {
				timeName = imp.Name.Name
			}
		}
		if strings.HasSuffix(path, paginatorPkg) {
			paginator = true
		}
	}
	entrypoint := strings.Contains("/"+rel, "/internal/entrypoints/")
	ast.Inspect(file, func(n ast.Node) bool {
		switch x := n.(type) {
		case *ast.CallExpr:
			if paginator && timeName != "" && isSetTimeoutWithUnit(x, timeName) {
				out = append(out, finding{pos(x.Pos()), "SetTimeout пагинатора — число секунд (SetTimeout(10)): хелпер сам умножает на time.Second (paginator-timeout-units)"})
			}
		case *ast.Field:
			if entrypoint && x.Tag != nil {
				tag, err := strconv.Unquote(x.Tag.Value)
				if err == nil {
					if msg := validateTag(reflect.StructTag(tag)); msg != "" {
						out = append(out, finding{pos(x.Tag.Pos()), msg})
					}
				}
			}
		}

		return true
	})

	return out
}

// validateTag: тег validate валидатор gin в SDK не читает (SetTagName("binding")). Совпадает с binding — мёртвый, но
// не ошибка; нет binding или правила другие — правила из validate не проверяются.
func validateTag(tag reflect.StructTag) string {
	v, ok := tag.Lookup("validate")
	if !ok {
		return ""
	}
	b, hasBinding := tag.Lookup("binding")
	switch {
	case !hasBinding:
		return "тег validate валидатор gin в SDK не читает, binding нет — правила не проверяются; перенести в binding:\"…\" (validate-tag-ignored)"
	case b != v:
		return fmt.Sprintf("validate:%q и binding:%q расходятся — проверяется только binding (validate-tag-ignored)", v, b)
	}

	return ""
}

func isSetTimeoutWithUnit(call *ast.CallExpr, timeName string) bool {
	sel, ok := call.Fun.(*ast.SelectorExpr)
	if !ok || sel.Sel.Name != "SetTimeout" || len(call.Args) != 1 {
		return false
	}

	return hasUnit(call.Args[0], timeName)
}

func hasUnit(e ast.Expr, timeName string) bool {
	switch x := e.(type) {
	case *ast.ParenExpr:
		return hasUnit(x.X, timeName)
	case *ast.BinaryExpr:
		return hasUnit(x.X, timeName) || hasUnit(x.Y, timeName)
	case *ast.SelectorExpr:
		id, ok := x.X.(*ast.Ident)

		return ok && id.Name == timeName && timeUnits[x.Sel.Name]
	}

	return false
}
