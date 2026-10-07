# Commands

`MPS\Core\Commands\Command`

Базовый класс для application-команд. Паттерн Command — инкапсулирует одну операцию с единственной точкой входа `exec()`.

## Использование

Наследовать и реализовать `prepare()`:

```php
use MPS\Core\Commands\Command;

class GenerateReportCommand extends Command
{
    public function __construct(
        private readonly ReportRepositoryInterface $repository,
        private readonly FileManagerInterface $fileManager,
    ) {}

    protected function prepare(): mixed
    {
        $data = $this->repository->getReportData();
        $path = $this->fileManager->store('reports/report.xlsx', $data);

        return $path;
    }
}

// вызов
$command = app(GenerateReportCommand::class);
$result  = $command->exec();
```

## Методы

```php
// точка входа — вызывает prepare()
public function exec(): mixed

// реализовать в наследнике
protected function prepare(): mixed
```

## Отличие от Artisan-команд

`MPS\Core\Commands\Command` — это не Artisan. Это доменный объект-команда, который можно вызвать из сервиса, контроллера или Artisan-команды:

```php
// из сервиса
$this->generateReportCommand->exec();

// из Artisan
class GenerateReportConsoleCommand extends \Illuminate\Console\Command
{
    protected $signature = 'reports:generate';

    public function handle(GenerateReportCommand $command): void
    {
        $command->exec();
        $this->info('Done');
    }
}
```
