# Стиль кода

---

## Именование

- Ресиверы — короткая форма, первая буква типа: `r *Repository`, `s *Service`, `h *Handler`
- Аббревиатуры в именах — все буквы заглавные: `ID`, `URL`, `HTTP`, `DTO`, `JSON`. Примеры: `createDTO` (не `createDto`), `userID` (не `userId`)
- Интерфейсы — по роли без суффикса `-er`: `Repository`, `Service`, `Handler`. Узкие интерфейсы-зависимости (`conventions.md` п. 12) — по действию или роли, `-er` допустим: `Publisher`, `Clock`, `TemplateReader`, `TokenVerifier`

---

## Импорты

Три группы, разделённые пустой строкой:

```go
import (
    "context"
    "errors"

    "gorm.io/gorm"
    "github.com/exgamer/gosdk-core/pkg/exception"

    tariffdomain "git.example.com/.../internal/domains/billing/tariff"
)
```

---

## Стиль кода

- `if err != nil` — сразу после вызова, не в конце функции
- Прямой `return` без промежуточной переменной где возможно:
  ```go
  // хорошо
  return repo.GetByID(ctx, id)

  // лишнее
  result, err := repo.GetByID(ctx, id)
  return result, err
  ```
- Пустая строка между логическими блоками внутри функции
- Перед `return`, `continue`, `break` — пустая строка. Исключение: если это первая строка внутри `if`, `for` и подобных конструкций:
  ```go
  // хорошо — первая строка в if
  if err != nil {
      return nil, err
  }

  // хорошо — пустая строка перед return
  result := compute()

  return result, nil

  // плохо — нет пустой строки
  result := compute()
  return result, nil
  ```
- Несколько структур в одном файле — только если они используются внутри пакета и связаны по смыслу:
  ```go
  // хорошо — model.go с двумя связанными моделями
  type tariff struct { ... }
  type tariffPeriod struct { ... }

  // плохо — несвязанные структуры в одном файле
  type tariff struct { ... }
  type session struct { ... }
  ```
- Цепочка из 3+ вызовов (builder-паттерн, fluent-style) — один вызов на строку, а не всё в одну строку:
  ```go
  // плохо — сливается в одну нечитаемую строку
  b := builder.NewPostHttpRequestBuilder[Response](ctx, url).SetRequestHeaders(headers).SetJSONBody(body).SetRequestTimeout(timeout).SetThrowUnmarshalError(false)

  // хорошо
  b := builder.NewPostHttpRequestBuilder[Response](ctx, url).
      SetRequestHeaders(headers).
      SetJSONBody(body).
      SetRequestTimeout(timeout).
      SetThrowUnmarshalError(false)
  ```
- Читается сверху вниз (`core/style.md`, «Форма кода»; **[ВНИМАНИЕ]**): вызов не вкладывается в вызов и не индексируется на месте, замыкание не передаёт поведение в свою функцию, функция-литерал — короткая, без затенения внешних переменных, глубина блоков ≤ 2. Один уровень с коротким аргументом допустим.

  ```go
  // допустимо: аргумент — один вызов маппера от входных данных
  result, err := h.workflow.Handle(ctx, mapper.ToInput(req))

  // плохо: вызов в аргументе и индекс по результату
  return toResponse(s.withMedia(ctx, []*Tariff{tariff})[0]), nil

  // хорошо
  tariffs := s.withMedia(ctx, []*Tariff{tariff})
  response := toResponse(tariffs[0])

  return response, nil
  ```
- Ранний выход вместо `else` (`core/style.md`, «Форма кода»; `else-after-return`): `if err != nil { return ..., err }` и дальше основной сценарий без вложенности; ветка завершилась `return`/`continue` — `else` после неё не пишется (gofmt-стиль, его же требует `golint`/`revive` правилом `indent-error-flow`); выбор по значению — `switch`.

  ```go
  // плохо
  if err != nil {
      return nil, err
  } else {
      if session.Closed {
          return session, nil
      } else {
          return w.close(ctx, session)
      }
  }

  // хорошо
  if err != nil {
      return nil, err
  }

  if session.Closed {
      return session, nil
  }

  return w.close(ctx, session)
  ```

---

## Комментарии

Правило одно для всех стеков — `core/style.md`, «Комментарии» и «Любой текст для людей» (что писать, чего не писать, уровни, теги `ai-style-comment`/`comment-restates-name`/`ai-style-text`). Go-специфика:

- Doc-комментарий начинается с идентификатора: `// GetTariff Тариф по ID: …`, `// GraceMinutes Доп. время на выезд (минуты). 0 = отключено` — так его показывает `godoc`.
- Swagger `@Summary`/`@Description` на хендлере — те же правила текста, что для комментариев.
- `golint`/`revive` требуют комментарий на каждом экспортируемом идентификаторе — это не повод писать пересказ имени: если сказать нечего, достаточно `// Name` без описания либо не экспортировать идентификатор.
