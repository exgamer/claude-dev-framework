// Command gocheck — правила фреймворка для Go, которых нет в golangci-lint: разбор AST, только стандартная библиотека.
// Запускает tools/lint/lint.py go; вывод — как у golangci-lint: «файл:строка:колонка: сообщение (тег)».
//
//	gocheck <корень проекта> [файл.go ...]   — без файлов: все .go проекта (кроме vendor/, testdata/, скрытых папок)
//	gocheck <корень проекта> -               — список файлов из stdin, по одному на строку (большой дифф — без длинной командной строки)
//
// Код возврата: 0 — чисто, 1 — есть находки, 2 — ошибка запуска.
package main

import (
	"fmt"
	"go/ast"
	"go/parser"
	"go/token"
	"io"
	"io/fs"
	"os"
	"path/filepath"
	"reflect"
	"regexp"
	"sort"
	"strconv"
	"strings"
)

// Имена файлов — каталог тегов stacks/go/review-tags.md.
var genericInterfaceFiles = map[string]bool{"ports.go": true, "interfaces.go": true, "dependencies.go": true}
var abstractionFiles = map[string]bool{
	"types.go": true, "const.go": true, "constants.go": true, "consts.go": true,
	"utils.go": true, "helpers.go": true, "common.go": true, "misc.go": true,
}

// Ссылки на артефакты задачи (core/style.md, «Комментарии»): ID пунктов design/плана/QA (R15, AC2, T3a), раздел
// design, путь docs/tasks/. Путь и раздел — в любом комментарии и строке. ID в комментарии — отдельным словом: до
// него начало, пробел, «(», «,» или «/», после — конец, пробел, «)», «,», «:», «;», «/» или точка в конце фразы;
// код в обратных кавычках — ссылка на код, не на артефакт. ID в строке (данные тестов и сообщения полны кодов —
// ключи JSON, имена полей, адреса ячеек, модели) — только в позиции метки: в начале перед «:» или тире
// (t.Run("T3: …"), "R15 — …") и в скобках после пробела (" (AC2)"); сырые строки `…` (SQL, JSON, исходники) — только путь
// и раздел.
const artifactIDs = `(?:A|L|T|R|AC|C|Th|RV|RF|N|W|F|P|Q)[0-9]+[a-z]?`

var artifactRefPath = regexp.MustCompile(`design §|docs/tasks/`)
var artifactRefInComment = regexp.MustCompile(`(?:^|[\s(,/])(` + artifactIDs + `)(?:$|[\s),:;/]|\.(?:\s|$))`)
var artifactRefInString = regexp.MustCompile(`^\s*(` + artifactIDs + `)(?:\s*:|\s+[—–-]\s)|\s\((` + artifactIDs + `)[),]`)
var backticked = regexp.MustCompile("`[^`]*`")

// Общепринятые обозначения вне артефактов задачи: форматы бумаги, уровни кеша.
var artifactRefAllowed = map[string]bool{"A3": true, "A4": true, "A5": true, "L1": true, "L2": true, "L3": true}

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
	if len(files) == 1 && files[0] == "-" {
		data, err := io.ReadAll(os.Stdin)
		if err != nil {
			fmt.Fprintln(os.Stderr, err)
			os.Exit(2)
		}
		files = nil
		for _, line := range strings.Split(string(data), "\n") {
			if line = strings.TrimRight(line, "\r"); strings.TrimSpace(line) != "" {
				files = append(files, line)
			}
		}
		if len(files) == 0 {
			fmt.Fprintln(os.Stderr, "gocheck: в stdin нет файлов")
			os.Exit(2)
		}
	}
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
	file, err := parser.ParseFile(fset, filepath.Join(root, rel), nil, parser.SkipObjectResolution|parser.ParseComments)
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
	for _, group := range file.Comments {
		for _, c := range group.List {
			if ref := artifactRef(backticked.ReplaceAllString(c.Text, "``"), artifactRefInComment); ref != "" {
				out = append(out, finding{pos(c.Pos()), artifactRefMsg(ref)})
			}
		}
	}
	skip := map[*ast.BasicLit]bool{}
	for _, imp := range file.Imports {
		skip[imp.Path] = true
	}
	entrypoint := strings.Contains("/"+rel, "/internal/entrypoints/")
	ast.Inspect(file, func(n ast.Node) bool {
		switch x := n.(type) {
		case *ast.CallExpr:
			if paginator && timeName != "" && isSetTimeoutWithUnit(x, timeName) {
				out = append(out, finding{pos(x.Pos()), "SetTimeout пагинатора — число секунд (SetTimeout(10)): хелпер сам умножает на time.Second (paginator-timeout-units)"})
			}
		case *ast.BasicLit:
			if x.Kind == token.STRING && !skip[x] {
				id := artifactRefInString
				if strings.HasPrefix(x.Value, "`") {
					id = nil
				}
				if text, err := strconv.Unquote(x.Value); err == nil {
					if ref := artifactRef(text, id); ref != "" {
						out = append(out, finding{pos(x.Pos()), artifactRefMsg(ref)})
					}
				}
			}
		case *ast.Field:
			if x.Tag != nil {
				skip[x.Tag] = true // тег поля — не текст
			}
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

// artifactRef — первая ссылка на артефакт задачи в тексте; id — шаблон ID для этого вида текста (nil — только путь и раздел).
func artifactRef(text string, id *regexp.Regexp) string {
	if m := artifactRefPath.FindString(text); m != "" {
		return m
	}
	if id == nil {
		return ""
	}
	for _, m := range id.FindAllStringSubmatch(text, -1) {
		ref := strings.Join(m[1:], "") // у шаблона строки — две группы, совпала одна
		if !artifactRefAllowed[ref] {
			return ref
		}
	}

	return ""
}

func artifactRefMsg(ref string) string {
	return fmt.Sprintf("ссылка на артефакт задачи %q: docs/tasks/ не в репозитории — написать, что и почему, без ID пункта (task-artifact-ref-in-code)", ref)
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
