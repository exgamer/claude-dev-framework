# git.mpinnovations.kz/mps/go-packages/gosdk-console-core v1.0.3

## `git.mpinnovations.kz/mps/go-packages/gosdk-console-core/pkg/app`

Package app — ConsoleKernel: тонкая обёртка над cobra для консольных команд, подключаемая как обычный gosdk-core KernelInterface.

- Константы: `ConsoleKernelName`
- `type ConsoleKernel struct`
  - `func NewConsoleKernel(use string) *ConsoleKernel` — NewConsoleKernel создаёт kernel с корневой cobra-командой. use — имя бинарника, показывается в usage/--help (например "console" для go run ./cmd/console).
  - `func (k *ConsoleKernel) AddCommand(cmds ...*cobra.Command) *ConsoleKernel` — AddCommand регистрирует одну или несколько cobra-команд в корне.
  - `func (k *ConsoleKernel) Init(a *app.App) error` — Init — no-op. Конфиг/подключения поднимают сами команды (в своих RunE) или другие kernels, зарегистрированные тем же RegisterAndInitKernels; к моменту Start (после Init всех kernels) окружение уже загружено через app.ens…
  - `func (k *ConsoleKernel) Name() string`
  - `func (k *ConsoleKernel) Start(a *app.App) error` — Start разбирает os.Args[1:] и выполняет сматченную cobra-команду. Ошибка выполнения команды репортится в Sentry (тегом - какая именно команда упала) и возвращается отсюда же - RunAll в gosdk-core пробрасывает её вызывающ…
  - `func (k *ConsoleKernel) Stop(ctx context.Context) error`

