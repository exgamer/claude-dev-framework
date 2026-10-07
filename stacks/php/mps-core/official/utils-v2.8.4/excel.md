# Excel (Reader)

`MPS\Utils\Components\Excel\ExcelReader`

Чтение XLS/XLSX файлов с поддержкой чанкования.

## Использование

```php
use MPS\Utils\Components\Excel\ExcelReader;

$reader = ExcelReader::make()
    ->setHeaderRowCount(1)   // пропустить строки заголовка
    ->setChunkSize(100);     // размер чанка

$reader->read($filePath, function (array $rows) {
    foreach ($rows as $row) {
        // обработать строку
    }
});
```

## Настройки

```php
$reader->setRowLimit(1000);              // ограничить кол-во строк
$reader->setHeaderRowCount(1);          // строк заголовка (пропускаются)
$reader->setChunkSize(500);             // строк в одном чанке callback
```

Поддерживает `.xls` и `.xlsx` — определяет формат автоматически по расширению.
