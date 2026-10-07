# FileSystem

## UploadManager — для загрузки файлов (актуальный)

`MPS\Utils\Components\FileSystem\UploadManager`

```php
$uploadManager->upload($file, $directory);
```

## FileManager — для остальных операций

`MPS\Utils\Components\FileSystem\FileManager`

Допустимые методы:

```php
$fileManager->get(string $path): string
$fileManager->has(string $path): bool
$fileManager->put(string $path, string $content): bool
$fileManager->getUrl(string $path): string
$fileManager->getTmpUrl(string $path, \DateTimeInterface $expiration): string
$fileManager->getFileInfo(string $path): array
```

Deprecated методы (не использовать):

```php
// ❌ deprecated since v2.3.0 — использовать UploadManager
$fileManager->upload($file, $dir);
$fileManager->uploadFromUrl($url, $dir);
```
