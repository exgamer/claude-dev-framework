# ws

WebSocket-пакет поверх [gin](https://github.com/gin-gonic/gin) + [gorilla/websocket](https://github.com/gorilla/websocket).

Три понятия:
- **Hub** — комната: управляет подключениями, рассылает события всем клиентам
- **Client** — одно соединение
- **Server** — HTTP→WS апгрейдер, привязывает маршруты к роутеру

---

## Push-only маршрут (клиент только слушает)

Самый распространённый сценарий: сервер пушит события, клиент ничего не шлёт.

```go
hub := ws.NewHub(ctx)
server := ws.NewServer(router, ws.Config{})

// одна строка — маршрут готов
server.Serve("/ws/v1/cities", hub)
```

---

## Middleware и аутентификация

`NewServer` принимает `gin.IRouter` — можно передать `*gin.RouterGroup` с уже навешанными middleware.

```go
wsGroup := router.Group("/ws")
wsGroup.Use(middleware.AuthMiddleware())   // проверяет JWT, кладёт user_id через c.Set
wsGroup.Use(middleware.LoggerMiddleware())

server := ws.NewServer(wsGroup, ws.Config{})
```

В `ServeFunc` gin.Context доступен в `onConnect` — middleware-значения уже установлены:

```go
server.Handle("/v1/chat", server.ServeFunc(hub, func(c *gin.Context, client *ws.Client) func([]byte) {
    userID := c.GetString("user_id") // установлен middleware.AuthMiddleware
    return func(raw []byte) {
        // обработка сообщений от аутентифицированного пользователя
    }
}))
```

Из сервисного слоя шлём события:

```go
func (s *Service) Create(ctx context.Context, model *City) (*City, error) {
    model, err := s.repository.Create(ctx, model)
    if err != nil {
        return nil, err
    }
    s.hub.Publish("city.created", model) // все подключённые клиенты получат событие
    return model, nil
}
```

`hub.Publish` сам маршалит конверт `{"type":"...","data":...}` и рассылает всем.

---

## Маршрут с обработкой входящих сообщений

Когда клиент тоже должен что-то отправлять на сервер.

```go
server.Handle("/ws/v1/chat", server.ServeFunc(hub, func(c *gin.Context, client *ws.Client) func([]byte) {
    // вызывается один раз при подключении
    return func(raw []byte) {
        // вызывается на каждое сообщение от этого клиента
        var msg ws.Message
        json.Unmarshal(raw, &msg)

        switch msg.Type {
        case "ping":
            client.Publish("pong", nil)         // ответ только этому клиенту
        case "chat.message":
            client.Hub().Broadcast(raw)          // переслать всем
        }
    }
}))
```

---

## Клиент (JavaScript)

> Сервер может упаковать несколько сообщений в один WebSocket-фрейм через `\n`.
> Разбивай каждый фрейм на строки перед парсингом.

```js
const ws = new WebSocket('ws://localhost:8080/ws/v1/cities')

ws.onmessage = (event) => {
    for (const line of event.data.split('\n')) {
        if (!line) continue
        const msg = JSON.parse(line)

        switch (msg.type) {
            case 'city.created':
                console.log('new city:', msg.data)
                break
            case 'city.updated':
                console.log('updated city:', msg.data)
                break
            case 'city.deleted':
                console.log('deleted city id:', msg.data.id)
                break
        }
    }
}
```

---

## Тестовый клиент

В корне пакета лежит `socket-test.html` — готовая страница для ручного тестирования сокетов в браузере.

Открыть локально:
```bash
open socket-test.html   # macOS
# или просто перетащить файл в браузер
```

Возможности:
- Два таба: push-only маршрут и интерактивный
- Поле `Authorization: Bearer <token>` — перед подключением делает preflight HTTP GET, чтобы показать реальный HTTP-статус (401, 403 и т.д.)
- Токен автоматически добавляется в URL как `?token=` (браузер не поддерживает кастомные WS-заголовки)
- Расшифровка кодов закрытия соединения (код 1006 — сервер отклонил апгрейд)
- Цветной лог событий по типу (`city.created`, `city.updated`, `city.deleted` и др.)

---

## Отладка (wscat)

```bash
wscat -c ws://localhost:8080/ws/v1/cities

# после REST POST /cities все клиенты получат:
< {"type":"city.created","data":{"id":1,"name":"Алматы","status":1}}

# после REST PUT /cities/1:
< {"type":"city.updated","data":{"id":1,"name":"Алматы updated","status":1}}

# после REST DELETE /cities/1:
< {"type":"city.deleted","data":{"id":1}}
```

---

## Config

| Поле              | Тип        | По умолчанию | Описание                              |
|-------------------|------------|--------------|---------------------------------------|
| `AllowedOrigins`  | `[]string` | `nil`        | nil или пустой список = разрешить все |
| `ReadBufferSize`  | `int`      | `1024`       | Размер буфера чтения (байт)           |
| `WriteBufferSize` | `int`      | `1024`       | Размер буфера записи (байт)           |

---

## Несколько Hub'ов

Каждый маршрут — свой Hub. Клиенты разных Hub'ов изолированы друг от друга.

```go
cityHub  := ws.NewHub(ctx)
orderHub := ws.NewHub(ctx)

server.Serve("/ws/v1/cities", cityHub)
server.Serve("/ws/v1/orders", orderHub)
```

Сервис городов пишет в `cityHub` — клиенты на `/ws/v1/orders` ничего не получают, и наоборот.
