# main.go — точка входа приложения

Файл лежит в корне проекта: `main.go`

Содержит только три вещи:
1. Swagger аннотации (`@title`, `@version`, `@description`, `@host`, `@BasePath`)
2. Инициализацию App через `app.NewApp()`
3. Запуск через `appInstance.RunAll()` и `appInstance.WaitForShutdown()`

Никакой бизнес-логики, никакой конфигурации — всё это в `internal/app/app.go`.

## Шаблон

```go
package main

import (
    "log"

    "git.mpinnovations.kz/.../internal/app"
)

// @title        Service Name (API)
// @version      1.0
// @description  Описание сервиса
// @host         0.0.0.0:8090
// @BasePath     /
func main() {
    appInstance, err := app.NewApp()
    if err != nil {
        log.Fatal(err)
    }

    err = appInstance.RunAll()
    if err != nil {
        log.Fatal(err)
    }

    appInstance.WaitForShutdown()
}
```
