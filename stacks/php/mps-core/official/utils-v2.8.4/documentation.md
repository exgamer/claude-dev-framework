# Documentation

`MPS\Utils\Components\Documentation\Swagger\SwaggerHelper`

Утилита для парсинга PHPDoc-комментариев с OpenAPI-аннотациями.

## SwaggerHelper

```php
use MPS\Utils\Components\Documentation\Swagger\SwaggerHelper;

$result = SwaggerHelper::parseDocComment($docComment);
$result = SwaggerHelper::parseDocComment($docComment, function (&$annotations) {
    // пост-обработка
});
```

## Artisan команда

```bash
php artisan mps:utils:swagger:scan
```

Сканирует аннотации и генерирует OpenAPI-спецификацию. Используется в `composer swagger`:

```bash
composer swagger
# = php artisan mps:utils:swagger:scan + php artisan l5-swagger:generate
```
