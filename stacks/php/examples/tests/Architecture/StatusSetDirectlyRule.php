<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
 * Тег status-set-directly: поле состояния (status, is_default) пишет только метод модели или метод репозитория
 * конкретного перехода — запись свойства модели, setAttribute() и ключ в массиве fill()/update()/create()… у модели,
 * builder-а или репозитория вне классов модели, *Repository и *Factory (начальный статус при создании —
 * regulations/entity.md). Тесты и database/ не проверяются.
 *
 * @implements Rule<Node\Expr>
 */
final class StatusSetDirectlyRule implements Rule
{
    private const FIELDS = ['status', 'is_default'];

    private const WRITE_METHODS = [
        'fill', 'forcefill', 'update', 'updatebyid', 'create', 'forcecreate', 'updateorcreate', 'firstorcreate',
        'firstornew', 'updateorinsert', 'insert', 'upsert', 'setattribute',
    ];

    private const STORAGE = [
        'Illuminate\Database\Eloquent\Model',
        'Illuminate\Database\Eloquent\Builder',
        'Illuminate\Database\Eloquent\Relations\Relation',
        'Illuminate\Database\Query\Builder',
    ];

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $file = str_replace('\\', '/', $scope->getFile());
        if (str_contains($file, '/tests/') || str_contains($file, '/database/')) {
            return [];
        }
        $class = $scope->getClassReflection();
        if ($class !== null && ($class->is(self::STORAGE[0]) || str_ends_with($class->getName(), 'Repository')
            || str_ends_with($class->getName(), 'Factory'))) {
            return [];
        }
        $field = $this->writtenField($node, $scope);
        if ($field === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('%s записан мимо перехода — только метод модели или метод репозитория конкретного перехода (status-set-directly)', $field))
                ->identifier('devFramework.statusSetDirectly')
                ->build(),
        ];
    }

    private function writtenField(Node $node, Scope $scope): ?string
    {
        if ($node instanceof Assign && $node->var instanceof PropertyFetch && $node->var->name instanceof Identifier) {
            $name = $node->var->name->toString();

            return in_array($name, self::FIELDS, true) && $this->isStorage($scope->getType($node->var->var)) ? $name : null;
        }
        if (!($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall)
            || !$node->name instanceof Identifier || !in_array(strtolower($node->name->toString()), self::WRITE_METHODS, true)) {
            return null;
        }
        $receiver = $node instanceof StaticCall
            ? ($node->class instanceof Name ? new ObjectType($scope->resolveName($node->class)) : null)
            : $scope->getType($node->var);
        if ($receiver === null || !$this->isStorage($receiver)) {
            return null;
        }
        $args = array_values(array_filter($node->args, static fn ($a): bool => $a instanceof Node\Arg));
        if (strtolower($node->name->toString()) === 'setattribute') {
            $first = $args[0]->value ?? null;

            return $first instanceof String_ && in_array($first->value, self::FIELDS, true) ? $first->value : null;
        }
        foreach ($args as $arg) {
            $found = $this->fieldInArray($arg->value);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function fieldInArray(Node\Expr $expr): ?string
    {
        if (!$expr instanceof Array_) {
            return null;
        }
        foreach ($expr->items as $item) {
            if ($item->key instanceof String_ && in_array($item->key->value, self::FIELDS, true)) {
                return $item->key->value;
            }
            $nested = $this->fieldInArray($item->value);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }

    private function isStorage(Type $type): bool
    {
        foreach (self::STORAGE as $storage) {
            if ((new ObjectType($storage))->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }
        foreach ($type->getObjectClassNames() as $name) {
            if (str_ends_with($name, 'Repository') || str_ends_with($name, 'RepositoryInterface')) {
                return true;
            }
        }

        return false;
    }
}
