<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
 * Тег model-has-eloquent-relations: в Eloquent-модели нет методов-связей — метод возвращает Relation
 * (по объявленному типу) или вызывает $this->hasMany()/belongsTo()/… Тесты не проверяются.
 *
 * @implements Rule<InClassMethodNode>
 */
final class EloquentRelationRule implements Rule
{
    private const RELATION_BUILDERS = [
        'hasone', 'hasmany', 'belongsto', 'belongstomany', 'hasonethrough', 'hasmanythrough',
        'morphto', 'morphone', 'morphmany', 'morphtomany', 'morphedbymany',
    ];

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (str_contains(str_replace('\\', '/', $scope->getFile()), '/tests/')) {
            return [];
        }
        $class = $node->getClassReflection();
        if (!$class->is('Illuminate\Database\Eloquent\Model')) {
            return [];
        }
        $method = $node->getMethodReflection();
        $returns = (new ObjectType('Illuminate\Database\Eloquent\Relations\Relation'))
            ->isSuperTypeOf($method->getVariants()[0]->getReturnType())->yes();
        $builds = (new NodeFinder())->findFirst($node->getOriginalNode()->stmts ?? [], static fn (Node $n): bool => $n instanceof MethodCall
            && $n->var instanceof Variable && $n->var->name === 'this'
            && $n->name instanceof Identifier && in_array(strtolower($n->name->toString()), self::RELATION_BUILDERS, true)) !== null;
        if (!$returns && !$builds) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('%s::%s() — Eloquent-связь в модели; связанные данные — методом репозитория (model-has-eloquent-relations)', $class->getName(), $method->getName()))
                ->identifier('devFramework.modelHasEloquentRelations')
                ->build(),
        ];
    }
}
