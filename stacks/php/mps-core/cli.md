# CLI канал (Artisan команды)

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они.

## Структура

Консоль — контекст `Console` в точках входа: `Entrypoints/Console/{Domain}/{Module}/` (как `parking_app/Entrypoints/Console/Billing/WhitelistOutbox/`), провайдер — `Entrypoints/Console/{Domain}/ServiceProvider.php`.

## ServiceProvider

```php
namespace App\ParkingApp\Entrypoints\CLI\YourDomain\YourModule\Providers;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'cli:your-domain:your-module';
    }
}
```

Зарегистрировать в родительском `App/Entrypoints/CLI/ServiceProvider.php`.

## commands.php

Два варианта регистрации:

```php
use App\Entrypoints\CLI\YourDomain\YourModule\Commands\YourConsoleCommand;
use App\Domains\YourDomain\Modules\YourModule\Commands\YourDomainCommand;
use Illuminate\Support\Facades\Artisan;

// Вариант 1 — класс команды (когда нужны опции, аргументы)
return [
    YourConsoleCommand::class,
];

// Вариант 2 — Artisan::command() для простых однострочников без опций
Artisan::command('your-domain:action', function () {
    app(YourDomainCommand::class)->exec();
})->setDescription('Описание команды');
```

Можно комбинировать оба варианта в одном файле.

## Artisan-команда

Тонкая оболочка: маппит CLI-опции в доменный Command и вызывает его. Бизнес-логики в `handle()` нет.

```php
namespace App\ParkingApp\Entrypoints\CLI\YourDomain\YourModule\Commands;

use App\Domains\YourDomain\Modules\YourModule\Commands\YourDomainCommand;
use Illuminate\Console\Command;

class YourConsoleCommand extends Command
{
    protected $signature = 'your-domain:module:action
        {--ids= : ID через запятую}
        {--async : Асинхронный режим}
        {--chunk-size= : Размер чанка}
    ';

    protected $description = 'Описание команды';

    public function handle(YourDomainCommand $command): void
    {
        if ($value = $this->option('ids')) {
            $command->setIds(explode(',', $value));
        }

        if ($this->option('async')) {
            $command->setAsync(true);
        }

        if ($value = $this->option('chunk-size')) {
            $command->setChunkSize((int) $value);
        }

        $command->exec();
    }
}
```

## Соглашения

- Signature: `{domain}:{module}:{action}` — через двоеточие
- Доменный Command инжектируется через параметр `handle()` — Laravel резолвит из контейнера
- Ошибки пробрасываются наверх — Laravel сам выводит их в консоль
