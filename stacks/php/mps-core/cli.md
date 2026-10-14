# CLI канал (Artisan команды)

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Структура

Консоль — контекст `Console` в точках входа: `Entrypoints/Console/{Domain}/{Module}/` (как `parking_app/Entrypoints/Console/Billing/WhitelistOutbox/`), провайдер — `Entrypoints/Console/{Domain}/ServiceProvider.php`.

## ServiceProvider

```php
namespace App\ParkingApp\Entrypoints\Console\YourDomain\YourModule\Providers;

use MPS\Core\Modules\ModuleServiceProvider;

class ServiceProvider extends ModuleServiceProvider
{
    public static function getName(): string
    {
        return 'console:your-domain:your-module';
    }
}
```

Зарегистрировать в родительском `Entrypoints/Console/{Domain}/ServiceProvider.php`.

## commands.php

Два варианта регистрации:

```php
use App\ParkingApp\Entrypoints\Console\YourDomain\YourModule\Commands\YourConsoleCommand;
use App\ParkingApp\Domains\YourDomain\Modules\YourModule\Commands\YourDomainCommand;
use Illuminate\Support\Facades\Artisan;

// Вариант 1 — класс команды (когда нужны опции, аргументы)
return [
    YourConsoleCommand::class,
];

// Вариант 2 — Artisan::command() для простых однострочников без опций
Artisan::command('your-domain:action', function () {
    app(YourDomainCommand::class)->execute();
})->setDescription('Описание команды');
```

Можно комбинировать оба варианта в одном файле.

## Artisan-команда

Тонкая оболочка: маппит CLI-опции в доменный Command и вызывает его. Бизнес-логики в `handle()` нет.

```php
namespace App\ParkingApp\Entrypoints\Console\YourDomain\YourModule\Commands;

use App\ParkingApp\Domains\YourDomain\Modules\YourModule\Commands\YourDomainCommand;
use Illuminate\Console\Command;

class YourConsoleCommand extends Command
{
    protected $signature = 'your-domain:module:action
        {--ids= : ID через запятую}
        {--chunk-size= : Размер чанка}
    ';

    protected $description = 'Описание команды';

    public function handle(YourDomainCommand $command): void
    {
        $ids = $this->option('ids') ? array_map('intval', explode(',', (string) $this->option('ids'))) : [];
        $chunkSize = (int) ($this->option('chunk-size') ?: 100);

        $command->execute(new YourActionParamsDto($ids, $chunkSize));
    }
}
```

## Соглашения

- Signature: `{domain}:{module}:{action}` — через двоеточие
- Доменный Command инжектируется через параметр `handle()` — Laravel резолвит из контейнера; точка входа у него `execute()` с DTO/типизированными аргументами, не fluent-сеттеры
- Ошибки пробрасываются наверх — Laravel сам выводит их в консоль; fail команды/крона уходит в Sentry (`../checklist.md` п. 23)
- Крон на нескольких инстансах — `withoutOverlapping()`/`onOneServer()` (`approaches/patterns/concurrency.md`)
