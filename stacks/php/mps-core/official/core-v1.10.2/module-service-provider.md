# ModuleServiceProvider

`MPS\Core\Modules\ModuleServiceProvider`

Базовый ServiceProvider с автозагрузкой ресурсов по соглашению. Наследник сам находит свои файлы относительно расположения своего класса — без явного указания путей.

## Автозагрузка

При `boot()` провайдер автоматически ищет и подключает:

| Ресурс | Путь (относительно провайдера) |
|---|---|
| Config | `../config.php` или `../config/*.php` |
| Routes | `../routes.php` или `../routes/*.php` |
| Definitions | `../definitions.php` или `../definitions/*.php` |
| Migrations | `../database/migrations/` |
| Translations | `../resources/lang/` |
| Commands | `../commands.php` или `../commands/*.php` |
| Views | `../resources/views/` |

Поддерживаются оба варианта: одиночный файл (`routes.php`) и директория (`routes/`).

## Использование

```php
use MPS\Core\Modules\ModuleServiceProvider;

class UserServiceProvider extends ModuleServiceProvider
{
    // ничего дополнительного не нужно —
    // все ресурсы подключаются автоматически
}
```

Если провайдер лежит в `app/Infrastructure/User/Providers/ServiceProvider.php`, то пути разрешаются относительно `app/Infrastructure/User/Providers/../` = `app/Infrastructure/User/`:
- `config.php` → `app/Infrastructure/User/config.php`
- `routes.php` → `app/Infrastructure/User/routes.php`
- `definitions.php` → `app/Infrastructure/User/Providers/definitions.php`
- `database/migrations/` → `app/Infrastructure/User/database/migrations/`

## Опция withSrc

По умолчанию `$withSrc = true` — провайдер срезает сегмент `/src` из пути к себе. Это позволяет размещать ресурсы рядом с `src/`, а не внутри.

```
InfrastructureModule/
├── src/
│   └── Providers/
│       └── ServiceProvider.php   ← провайдер здесь
├── config.php                    ← ресурсы здесь (на уровне с src)
├── routes.php
└── database/
    └── migrations/
```

При `$withSrc = false` ресурсы ищутся рядом с самим провайдером.

## Реестр модулей

Все загруженные модули регистрируются в статическом реестре:

```php
ModuleServiceProvider::getLoadModules(); // ['hash_module' => ServiceProvider::class, ...]
```

## Definitions

Биндинги из `definitions.php` применяются через `DefinitionHelper::resolve()`. Формат файла описан в [container.md](container.md#definitionhelper).

```php
// definitions.php
return [
    ['abstract' => UserRepositoryInterface::class, 'concrete' => UserRepository::class],
    ['abstract' => UserServiceInterface::class,    'concrete' => UserService::class],
];
```
