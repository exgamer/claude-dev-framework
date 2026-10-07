# ExcelWriter

`MPS\Utils\Components\ExcelWriter\ExcelWriter`

Запись Excel (XLSX) и ODS файлов. Основан на `openspout/openspout`.

## Использование

```php
use MPS\Utils\Components\ExcelWriter\ExcelWriter;

// Запись в файл
$writer = new ExcelWriter('/path/to/output.xlsx');
$writer->addRow(['Column1', 'Column2', 'Column3']);
$writer->addRow(['Value1', 'Value2', 'Value3']);
$writer->finish();

// Отдать браузеру напрямую
$writer = new ExcelWriter('report.xlsx', toBrowser: true);
$writer->addRow(['ID', 'Name'])->finish();
```

> Всегда вызывать `finish()` в конце — без него файл будет повреждён. Исключение: `getStream()` вызывает `finish()` автоматически.

## Варианты addRow

```php
// массив
$writer->addRow(['A', 'B', 'C']);

// callable
$writer->addRow(function () {
    return ['A', 'B', 'C'];
});

// Row объект
use OpenSpout\Common\Entity\Row;
$writer->addRow(Row::fromValues(['A', 'B']));

// с применением стиля
use OpenSpout\Common\Entity\Style\Style;
$writer->addRowFromArray(['Header'], (new Style())->setFontBold());
```

## Потоковая запись больших данных

`addRows()` принимает `GeneratorInterface` — эффективно для больших наборов:

```php
$writer->addRows(new class implements \MPS\Utils\Components\ExcelWriter\Interfaces\GeneratorInterface {
    public function generate(): \Generator
    {
        foreach (range(1, 100000) as $i) {
            yield [$i, 'Name ' . $i];
        }
    }
});
```

## Получение stream для HTTP-ответа

```php
$writer = new ExcelWriter($filePath);
$writer->addRow(['foo', 'bar']);
$stream = $writer->getStream(); // вызывает finish() автоматически
```

## Листы

```php
$sheet = $writer->getCurrentSheet();
$sheet->setName('Sheet Name');

$writer->addNewSheet();
```

Определяет формат по расширению: `.xlsx` → XLSX Writer, `.ods` → ODS Writer.
