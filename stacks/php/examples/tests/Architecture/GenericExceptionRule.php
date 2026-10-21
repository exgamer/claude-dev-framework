<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
 * Тег exception-not-domain-type: бросать только исключения ядра (*AppException), не встроенные исключения PHP.
 * Тесты и миграции не проверяются.
 *
 * @implements Rule<Node\Expr>
 */
final class GenericExceptionRule implements Rule
{
    public function __construct(private readonly ReflectionProvider $reflection)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $file = str_replace('\\', '/', $scope->getFile());
        if (str_contains($file, '/tests/') || str_contains($file, '/database/migrations/')) {
            return [];
        }
        $name = null;
        if ($node instanceof New_ && $node->class instanceof Name) {
            $name = $node->class;
        } elseif ($node instanceof FuncCall && $node->name instanceof Name
            && in_array(strtolower($node->name->toString()), ['throw_if', 'throw_unless'], true)
            && isset($node->args[1]) && $node->args[1] instanceof Node\Arg
            && $node->args[1]->value instanceof ClassConstFetch && $node->args[1]->value->class instanceof Name) {
            $name = $node->args[1]->value->class;
        }
        if ($name === null) {
            return [];
        }
        $class = ltrim($scope->resolveName($name), '\\');
        if (!$this->reflection->hasClass($class)) {
            return [];
        }
        $reflection = $this->reflection->getClass($class);
        if (!$reflection->isBuiltin() || !$reflection->implementsInterface('Throwable')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('\\%s — встроенное исключение PHP; только исключения ядра (NotFoundAppException, ValidationAppException, …) (exception-not-domain-type)', $reflection->getName()))
                ->identifier('devFramework.exceptionNotDomainType')
                ->build(),
        ];
    }
}
