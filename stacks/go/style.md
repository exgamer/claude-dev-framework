# Стиль кода

---

## Именование

- Ресиверы — короткая форма, первая буква типа: `r *Repository`, `s *Service`, `h *Handler`
- Аббревиатуры в именах — все буквы заглавные: `ID`, `URL`, `HTTP`, `DTO`, `JSON`. Примеры: `createDTO` (не `createDto`), `userID` (не `userId`)
- Интерфейсы — по роли без суффикса `-er`: `Repository`, `Service`, `Handler`

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
  return repo.GetById(ctx, id)

  // лишнее
  result, err := repo.GetById(ctx, id)
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

---

## Комментарии

- Комментарий объясняет **почему**, а не **что** — если убрать комментарий и код останется таким же понятным, комментарий лишний
- Запрещены комментарии, пересказывающие код построчно или объясняющие очевидное ("создаём структуру", "проверяем ошибку", "возвращаем результат") — типичный признак сгенерированного, а не осмысленного комментария; помечать **[ВНИМАНИЕ]** с предложением сократить или удалить
- Длинные многострочные комментарии-простыни над простой функцией/структурой, которые пересказывают весь код ниже вместо одной ёмкой мысли о неочевидном — **[ВНИМАНИЕ]**: если объяснение нужно, оно должно быть коротким и по существу (конкретная причина/ограничение/баг, который фиксит код), а не пересказом реализации

---

