<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Architecture;

use App\ParkingApp\ServiceProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionNamedType;
use SplFileInfo;

/**
 * Common — бизнес-словарь без зависимостей: не импортирует слои подпроекта и фреймворк, конструкторы — без сервисов.
 *
 * @author Имя Фамилия <name@example.com>
 */
class CommonBoundaryTest extends TestCase
{
    private const COMMON_NAMESPACE = 'App\\ParkingApp\\Common\\';

    /** Импорт с таким префиксом запрещён: остальные слои подпроекта, фреймворк, ядро */
    private const FORBIDDEN_PREFIXES = [
        'App\\ParkingApp\\',
        'Illuminate\\',
        'MPS\\',
    ];

    /** Разрешено поверх запретов: сам Common, базовый enum ядра */
    private const ALLOWED_IMPORTS = [
        self::COMMON_NAMESPACE,
        'MPS\\Core\\Enums\\Enumerable',
        'MPS\\Core\\Enums\\EnumerableTrait',
    ];

    /** Типы параметров конструктора вне Common, которые не зависимость: даты */
    private const ALLOWED_CONSTRUCTOR_TYPES = [
        'Carbon\\',
        'DateTimeInterface',
        'DateTimeImmutable',
    ];

    public function test_common_imports_nothing_from_other_layers_and_framework(): void
    {
        $violations = [];

        foreach ($this->commonFiles() as $file) {
            $violations = [...$violations, ...$this->importViolations($file)];
        }

        $this->assertSame([], $violations);
    }

    public function test_common_constructors_take_no_dependencies(): void
    {
        $violations = [];

        foreach ($this->commonFiles() as $file) {
            $violations = [...$violations, ...$this->constructorViolations($file)];
        }

        $this->assertSame([], $violations);
    }

    /**
     * Запрещённые импорты файла.
     *
     * @return list<string>
     */
    private function importViolations(SplFileInfo $file): array
    {
        $violations = [];

        foreach ($this->imports($file) as $import) {
            if ($this->isForbiddenImport($import)) {
                $violations[] = $file->getPathname() . ': use ' . $import;
            }
        }

        return $violations;
    }

    /**
     * Параметры конструктора класса файла, которые — зависимость (не скаляр, не Common, не дата).
     *
     * @return list<string>
     */
    private function constructorViolations(SplFileInfo $file): array
    {
        $reflection = new ReflectionClass($this->className($file));
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return [];
        }

        $violations = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $this->isAllowedConstructorType($type)) {
                continue;
            }

            $violations[] = $file->getPathname() . ': $' . $parameter->getName() . ' ' . $type;
        }

        return $violations;
    }

    /**
     * @return list<SplFileInfo>
     */
    private function commonFiles(): array
    {
        $provider = new ReflectionClass(ServiceProvider::class);
        $providerPath = (string) $provider->getFileName();
        $root = dirname($providerPath) . '/Common';
        $files = [];

        $directory = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator($directory) as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        $this->assertNotEmpty($files, 'В Common нет файлов: тест границ ничего не проверил');

        return $files;
    }

    /**
     * Импорты верхнего уровня файла; групповой `use A\{B, C}` — каждым именем.
     *
     * @return list<string>
     */
    private function imports(SplFileInfo $file): array
    {
        $source = (string) file_get_contents($file->getPathname());
        preg_match_all('/^use\s+(?:function\s+|const\s+)?([^;]+);/m', $source, $matches);

        $imports = [];

        foreach ($matches[1] as $statement) {
            $statement = (string) preg_replace('/\s+as\s+\w+/', '', $statement);

            if (! preg_match('/^([^{]+)\{([^}]*)}$/', trim($statement), $group)) {
                $imports[] = trim($statement);

                continue;
            }

            foreach (explode(',', $group[2]) as $name) {
                $imports[] = trim($group[1]) . trim($name);
            }
        }

        return $imports;
    }

    private function isForbiddenImport(string $import): bool
    {
        $import = ltrim($import, '\\');

        foreach (self::ALLOWED_IMPORTS as $allowed) {
            if ($import === $allowed || str_starts_with($import, $allowed)) {
                return false;
            }
        }

        foreach (self::FORBIDDEN_PREFIXES as $prefix) {
            if (str_starts_with($import, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedConstructorType(ReflectionNamedType $type): bool
    {
        if ($type->isBuiltin() || str_starts_with($type->getName(), self::COMMON_NAMESPACE)) {
            return true;
        }

        foreach (self::ALLOWED_CONSTRUCTOR_TYPES as $allowed) {
            if (str_starts_with($type->getName(), $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return class-string
     */
    private function className(SplFileInfo $file): string
    {
        $source = (string) file_get_contents($file->getPathname());
        preg_match('/^namespace\s+([^;]+);/m', $source, $namespace);

        /** @var class-string */
        return ($namespace[1] ?? '') . '\\' . $file->getBasename('.php');
    }
}
