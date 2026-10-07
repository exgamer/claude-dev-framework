// goref — справочник по Go-модулю: пакеты, экспортированные типы, функции, методы и константы
// с первой строкой doc-комментария. Читает только исходники, ничего не компилирует.
//
//	go run . <module-dir> [<module-dir>...] > reference.md
package main

import (
	"bytes"
	"fmt"
	"go/ast"
	"go/doc"
	"go/parser"
	"go/printer"
	"go/token"
	"io/fs"
	"os"
	"path/filepath"
	"sort"
	"strings"
)

func main() {
	if len(os.Args) < 2 {
		fmt.Fprintln(os.Stderr, "usage: goref <module-dir> [<module-dir>...]")
		os.Exit(2)
	}

	for _, dir := range os.Args[1:] {
		if err := module(dir); err != nil {
			fmt.Fprintln(os.Stderr, err)
			os.Exit(1)
		}
	}
}

func module(root string) error {
	modPath, version := modInfo(root)
	fmt.Printf("# %s %s\n\n", modPath, version)

	dirs := map[string]bool{}
	err := filepath.WalkDir(root, func(path string, d fs.DirEntry, err error) error {
		if err != nil {
			return err
		}

		if d.IsDir() && (strings.HasPrefix(d.Name(), ".") || d.Name() == "testdata" || d.Name() == "vendor" || d.Name() == "examples") && path != root {
			return filepath.SkipDir
		}

		if !d.IsDir() && strings.HasSuffix(path, ".go") && !strings.HasSuffix(path, "_test.go") {
			dirs[filepath.Dir(path)] = true
		}

		return nil
	})
	if err != nil {
		return err
	}

	list := make([]string, 0, len(dirs))
	for d := range dirs {
		list = append(list, d)
	}

	sort.Strings(list)

	for _, d := range list {
		if err := pkg(root, modPath, d); err != nil {
			return err
		}
	}

	return nil
}

func modInfo(root string) (string, string) {
	data, _ := os.ReadFile(filepath.Join(root, "go.mod"))
	modPath := ""

	for _, line := range strings.Split(string(data), "\n") {
		if strings.HasPrefix(line, "module ") {
			modPath = strings.TrimSpace(strings.TrimPrefix(line, "module "))
		}
	}

	version := ""
	if i := strings.LastIndex(root, "@"); i >= 0 {
		version = strings.TrimSuffix(root[i+1:], "/")
	}

	return modPath, version
}

func pkg(root, modPath, dir string) error {
	fset := token.NewFileSet()
	pkgs, err := parser.ParseDir(fset, dir, func(fi fs.FileInfo) bool {
		return !strings.HasSuffix(fi.Name(), "_test.go")
	}, parser.ParseComments)
	if err != nil {
		return err
	}

	rel, _ := filepath.Rel(root, dir)
	importPath := modPath

	if rel != "." {
		importPath += "/" + filepath.ToSlash(rel)
	}

	for _, p := range pkgs {
		if strings.HasSuffix(p.Name, "_test") || p.Name == "main" {
			continue
		}

		files := make([]*ast.File, 0, len(p.Files))
		for _, f := range p.Files {
			files = append(files, f)
		}

		d, err := doc.NewFromFiles(fset, files, importPath)
		if err != nil {
			return err
		}

		if len(d.Funcs) == 0 && len(d.Types) == 0 && len(d.Consts) == 0 && len(d.Vars) == 0 {
			continue
		}

		fmt.Printf("## `%s`\n\n", importPath)

		if s := firstLine(d.Doc); s != "" {
			fmt.Printf("%s\n\n", s)
		}

		values("Константы", fset, d.Consts)
		values("Переменные", fset, d.Vars)

		for _, f := range d.Funcs {
			item(fset, f.Decl, f.Doc)
		}

		for _, t := range d.Types {
			fmt.Printf("- `type %s %s`%s\n", t.Name, typeKind(t.Decl), suffix(t.Doc))

			for _, f := range t.Funcs {
				fmt.Print("  ")
				item(fset, f.Decl, f.Doc)
			}

			for _, m := range t.Methods {
				fmt.Print("  ")
				item(fset, m.Decl, m.Doc)
			}

			for _, c := range t.Consts {
				fmt.Printf("  - константы: %s\n", names(c))
			}
		}

		fmt.Println()
	}

	return nil
}

func values(title string, fset *token.FileSet, vals []*doc.Value) {
	for _, v := range vals {
		fmt.Printf("- %s: %s%s\n", title, names(v), suffix(v.Doc))
	}
}

func names(v *doc.Value) string {
	n := v.Names
	if len(n) > 12 {
		n = append(n[:12:12], "…")
	}

	return "`" + strings.Join(n, "`, `") + "`"
}

func item(fset *token.FileSet, decl *ast.FuncDecl, docText string) {
	d := *decl
	d.Body = nil
	d.Doc = nil

	var buf bytes.Buffer
	_ = printer.Fprint(&buf, fset, &d)
	sig := strings.Join(strings.Fields(buf.String()), " ")
	fmt.Printf("- `%s`%s\n", sig, suffix(docText))
}

func typeKind(decl *ast.GenDecl) string {
	for _, s := range decl.Specs {
		ts, ok := s.(*ast.TypeSpec)
		if !ok {
			continue
		}

		switch t := ts.Type.(type) {
		case *ast.StructType:
			return "struct"
		case *ast.InterfaceType:
			methods := []string{}
			for _, m := range t.Methods.List {
				for _, n := range m.Names {
					methods = append(methods, n.Name)
				}
			}

			return "interface{" + strings.Join(methods, ", ") + "}"
		case *ast.Ident:
			return t.Name
		default:
			return "…"
		}
	}

	return ""
}

func suffix(docText string) string {
	if s := firstLine(docText); s != "" {
		return " — " + s
	}

	return ""
}

func firstLine(text string) string {
	text = strings.TrimSpace(text)
	if text == "" {
		return ""
	}

	line := strings.SplitN(text, "\n\n", 2)[0]
	line = strings.Join(strings.Fields(line), " ")

	if r := []rune(line); len(r) > 220 {
		line = string(r[:220]) + "…"
	}

	return line
}
