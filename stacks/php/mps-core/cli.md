# CLI канал (Artisan команды)

> Справочник по использованию `mps/core` / `mps/utils` (перенесён из `claude-skills/php-arch`, пути приведены к структуре фреймворка). **Где класть файлы — `../structure.md`**, что есть в ядре и с какой версии — `capabilities.md`, официальная дока — `official/`. При расхождении главнее они — в фактах API (что есть и как вызывается); что из этого использовать — по правилам фреймворка (`../conventions.md`): примеры `official/` с `expanded`, `*OrFail`, суффиксом `DTO` — не образец.

## Структура

Консоль — контекст `Console` в точках входа (образец — `stacks/php/examples/parking_app/Entrypoints/Console/Billing/`):

```
Entrypoints/Console/{Domain}/
├── ServiceProvider.php                      ← один на домен, extends MPS\Core\Modules\ModuleServiceProvider
├── commands.php                             ← список команд домена
└── {Module}/{Action}ConsoleCommand.php      ← команда модуля, без подкаталога Commands/
```

Провайдера на уровне модуля нет. Провайдер домена регистрируется в провайдере подпроекта (`../structure.md`).

## ServiceProvider

```php
namespace App\ParkingApp\Entrypoints\Console\YourDomain;

use MPS\Core\Modules\ModuleServiceProvider as CoreServiceProvider;

class ServiceProvider extends CoreServiceProvider
{
}
```

Образец — `stacks/php/examples/parking_app/Entrypoints/Console/Billing/ServiceProvider.php`.

## commands.php

`Entrypoints/Console/{Domain}/commands.php` возвращает классы команд всех модулей домена (образец — `stacks/php/examples/parking_app/Entrypoints/Console/Billing/commands.php`):

```php
use App\ParkingApp\Entrypoints\Console\YourDomain\YourModule\YourActionConsoleCommand;

return [
    YourActionConsoleCommand::class,
];
```

`Artisan::command('…', fn () => …)` для однострочника без опций допустим в том же файле, но эталон его не использует: класс команды проверяет аргументы и тестируется.

## Artisan-команда

Тонкая оболочка: проверяет и маппит аргументы CLI, вызывает доменный Command, возвращает код выхода. Бизнес-логики в `handle()` нет. Образец — `stacks/php/examples/parking_app/Entrypoints/Console/Billing/Tariffs/SetDefaultTariffConsoleCommand.php`.

```php
namespace App\ParkingApp\Entrypoints\Console\YourDomain\YourModule;

use App\ParkingApp\Domains\YourDomain\Modules\YourModule\Commands\YourDomainCommand;
use Illuminate\Console\Command;

class YourActionConsoleCommand extends Command
{
    protected $signature = 'your-domain:your-module:action
        {id : ID записи}
    ';

    protected $description = 'Описание команды';

    public function handle(YourDomainCommand $command): int
    {
        $id = (string) $this->argument('id');

        if (! ctype_digit($id) || (int) $id === 0) {
            $this->error('ID — целое положительное число');

            return self::INVALID;
        }

        $command->execute((int) $id, null);   // консоль без пользователя: скоуп null явно (architecture/layers.md, В-19)

        return self::SUCCESS;
    }
}
```

## Соглашения

- Signature: `{domain}:{module}:{action}` — через двоеточие
- Доменный Command инжектируется через параметр `handle()` — Laravel резолвит из контейнера; точка входа у него `execute()` с DTO/типизированными аргументами, не fluent-сеттеры
- Ошибки пробрасываются наверх — Laravel сам выводит их в консоль; fail команды/крона уходит в Sentry (`../checklist.md` п. 23)
- Крон на нескольких инстансах — `withoutOverlapping()`/`onOneServer()` (`approaches/patterns/concurrency.md`)
