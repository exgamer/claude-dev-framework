# git.mpinnovations.kz/mps/go-packages/gosdk-websocket v1.0.4

## `git.mpinnovations.kz/mps/go-packages/gosdk-websocket`

- `func NewUpgrader(cfg Config) *websocket.Upgrader` — NewUpgrader returns a websocket.Upgrader built from Config.
- `type Client struct` — Client is a single WebSocket connection managed by a Hub.
  - `func NewClient(hub *Hub, conn *websocket.Conn) *Client`
  - `func (c *Client) Hub() *Hub` — Hub returns the Hub this client belongs to. Use in onMessage callbacks to broadcast or inspect state.
  - `func (c *Client) Publish(eventType string, data any) error` — Publish marshals a typed event envelope and sends it to this client only. Returns an error if marshaling fails.
  - `func (c *Client) ReadPump(onMessage func([]byte))` — ReadPump reads messages from the WebSocket connection and calls onMessage for each. onMessage is bound to this client at connection time via Server.ServeFunc. Must be called in a separate goroutine. Closes the connection…
  - `func (c *Client) Register() bool` — Register sends the client to the hub's register channel. Returns false if the hub has already exited; the connection is closed in that case.
  - `func (c *Client) Send(message []byte)` — Send delivers a raw message to this specific client only. If the client's send buffer is full or the hub has exited, the message is dropped.
  - `func (c *Client) WritePump()` — WritePump writes messages from the send channel to the WebSocket connection. Must be called in a separate goroutine. Sends periodic pings to keep the connection alive.
- `type Config struct` — Config holds WebSocket server configuration.
- `type Hub struct` — Hub manages active WebSocket clients and broadcasts messages. All mutations happen in a single goroutine (run), so no mutex is needed.
  - `func NewHub(ctx context.Context) *Hub` — NewHub creates a Hub and starts its run goroutine tied to ctx.
  - `func (h *Hub) Broadcast(message []byte)` — Broadcast sends a raw message to all connected clients. Safe to call on a nil *Hub (no-op).
  - `func (h *Hub) Publish(eventType string, data any) error` — Publish marshals a typed event envelope and sends it to all connected clients. Returns an error if marshaling fails. Safe to call on a nil *Hub (no-op).
- `type Message struct` — Message is the envelope for all client↔server messages.
- `type Server struct` — Server manages the WebSocket upgrader and gin router. It is shared across all WS routes; each route has its own Hub. router accepts *gin.Engine or *gin.RouterGroup — pass a group to apply middleware.
  - `func NewServer(router gin.IRouter, cfg Config) *Server` — NewServer creates a Server ready for route registration. router can be *gin.Engine or *gin.RouterGroup — use a group to attach middleware:
  - `func (s *Server) Handle(path string, handlers ...gin.HandlerFunc) *Server` — Handle registers a GET route. Returns the Server for chaining.
  - `func (s *Server) Serve(path string, hub *Hub) *Server` — Serve registers a push-only route: clients receive hub events, incoming messages are ignored.
  - `func (s *Server) ServeFunc(hub *Hub, onConnect func(*gin.Context, *Client) func([]byte)) gin.HandlerFunc` — ServeFunc returns a gin.HandlerFunc that upgrades HTTP→WebSocket using hub. onConnect is called once per connection with the gin.Context (available middleware values like user_id are accessible via c.Get) and the new Cli…

