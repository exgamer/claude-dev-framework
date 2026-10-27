"""Шаблоны правил PHP для phpstan (phpat + phpstan-disallowed-calls): класс архитектурных правил и neon.

Эталон (`stacks/php/examples/tests/Architecture/LayersArchitecture.php`, `phpstan-rules.neon`) — вывод этих функций
для `App\\ParkingApp`, доменов `Billing`, `Catalog` и их модулей (`domain_modules`); сверка — `tools/check/lint-rules.py`.
"""
import re
from pathlib import Path

LAYERS = ("Core", "Domains", "Entrypoints", "Infrastructure", "Workflows")


def snake(name: str) -> str:
    return re.sub(r"(?<!^)(?=[A-Z])", "_", name).lower()


def regex_root(root: str) -> str:
    """App\\ParkingApp → App\\\\\\\\ParkingApp для PHP-строки с регулярным выражением в одинарных кавычках."""
    return root.replace("\\", "\\\\\\\\")


# Общий модуль проекта внутри Domains (старая раскладка) — не домен: его знает любой домен, сам он доменов не знает
SHARED = ("Common", "Shared")


def domain_modules(dom_dir: Path, src_domains=frozenset()) -> dict[str, list[str]]:
    """Модули каждого домена — каталоги `Domains/{Домен}/Modules/*`; `Common` в списке — у домена есть `Domains/{Домен}/Common/`.
    Домены из `src_domains` — раскладка `Domains/{Домен}/src/Modules/*` (и `src/Common/`): psr-4 проекта отображает
    `Domains/{Домен}/src` на `…\\Domains\\{Домен}`, поэтому неймспейс модуля тот же, что у `Domains/{Домен}/Modules/*`."""
    out = {}
    for d in sorted(p for p in dom_dir.iterdir() if p.is_dir()) if dom_dir.is_dir() else []:
        bases = [d] + ([d / "src"] if d.name in src_domains else [])
        names = sorted({p.name for b in bases if (b / "Modules").is_dir() for p in (b / "Modules").iterdir() if p.is_dir()})
        out[d.name] = names + (["Common"] if any((b / "Common").is_dir() for b in bases) else [])
    return out


def render_arch(namespace: str, root: str, domains: list[str], modules: dict[str, list[str]] | None = None) -> str:
    modules = modules or {}
    rr = regex_root(root)
    # Domains/Common, Domains/Shared — общий модуль проекта внутри Domains (старая раскладка), не домен: его знает любой домен,
    # а сам он не знает ни доменов, ни внешних слоёв
    shared = [d for d in domains if d in SHARED]
    domains = [d for d in domains if d not in SHARED]
    out = [f"""<?php

declare(strict_types=1);

namespace {namespace};

use PHPat\\Selector\\Selector;
use PHPat\\Selector\\SelectorInterface;
use PHPat\\Test\\Builder\\Rule;
use PHPat\\Test\\PHPat;

/**
 * Границы слоёв и доменов подпроекта — проверяет phpstan (phpat); собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
 *
 * @author Имя Фамилия <name@example.com>
 */
final class LayersArchitecture
{{
    private const ROOT = '{root}';

    /** Eloquent-модель — доменная модель: лежит в Infrastructure, но домен и Response её знают. */
    private const MODELS = '/^{rr}\\\\\\\\Infrastructure\\\\\\\\.+\\\\\\\\Models\\\\\\\\/';

    public function test_domain_does_not_know_outer_layers(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\\Domains'))
            ->shouldNotDependOn()
            ->classes(
                self::infrastructureWithoutModels(),
                Selector::inNamespace(self::ROOT . '\\Entrypoints'),
                Selector::inNamespace(self::ROOT . '\\Workflows'),
            )
            ->because('домен не знает инфраструктуру (кроме Eloquent-модели), точки входа и workflow — порт в домене, адаптер в Infrastructure');
    }}
"""]
    for d in domains:
        others = [o for o in domains if o != d]
        if not others:
            continue
        targets = "\n".join(f"                Selector::inNamespace(self::ROOT . '\\Domains\\{o}')," for o in others)
        out.append(f"""
    public function test_{snake(d)}_does_not_know_other_domains(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\\Domains\\{d}'))
            ->shouldNotDependOn()
            ->classes(
{targets}
            )
            ->because('чужой домен — через workflow или Common');
    }}
""")
    # Модуль домена не знает соседний модуль того же домена (правило на каждый модуль домена из двух и больше модулей);
    # Common домена — общее его модулей — не знает ни один из них
    for d in domains:
        mods = [m for m in modules.get(d, []) if m != "Common"]
        for m in mods + (["Common"] if "Common" in modules.get(d, []) else []):
            others = mods if m == "Common" else [o for o in mods if o != m]
            if not others:
                continue
            src = f"Domains\\{d}\\Common" if m == "Common" else f"Domains\\{d}\\Modules\\{m}"
            name = f"test_{snake(d)}_common_does_not_know_modules" if m == "Common" else f"test_{snake(d)}_{snake(m)}_does_not_know_other_modules"
            why = (f"Common домена {d} — общее его модулей: модули его знают, он их — нет" if m == "Common"
                   else f"соседний модуль домена {d} — интерфейс у потребителя, DI связывает его с соседом; общее модулей — Domains/{d}/Common")
            targets = "\n".join(f"                Selector::inNamespace(self::ROOT . '\\Domains\\{d}\\Modules\\{o}')," for o in others)
            out.append(f"""
    public function {name}(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\\{src}'))
            ->shouldNotDependOn()
            ->classes(
{targets}
            )
            ->because('{why}');
    }}
""")
    for s in shared if domains else []:
        targets = "\n".join(f"                Selector::inNamespace(self::ROOT . '\\Domains\\{o}')," for o in domains)
        out.append(f"""
    public function test_domains_{snake(s)}_has_no_domain_dependencies(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\\Domains\\{s}'))
            ->shouldNotDependOn()
            ->classes(
{targets}
            )
            ->because('Domains/{s} — общий модуль проекта: его знают домены, сам он доменов не знает — то же правило, что Common/ (common-with-logic)');
    }}
""")
    layers = "\n".join(f"                Selector::inNamespace(self::ROOT . '\\{l}')," for l in LAYERS)
    out.append(f"""
    public function test_common_has_no_layer_dependencies(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\\Common'))
            ->shouldNotDependOn()
            ->classes(
{layers}
            )
            ->because('Common — общий словарь без зависимостей от слоёв');
    }}

    public function test_workflow_does_not_call_workflow(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::classname('/^{rr}\\\\\\\\Workflows\\\\\\\\.+Workflow$/', true))
            ->shouldNotDependOn()
            ->classes(Selector::classname('/^{rr}\\\\\\\\Workflows\\\\\\\\.+Workflow$/', true))
            ->because('workflow не вызывает другой workflow — общее выносится в домены');
    }}

    public function test_entrypoint_does_not_know_infrastructure(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\\Entrypoints'))
            ->shouldNotDependOn()
            ->classes(self::infrastructureWithoutModels())
            ->because('точка входа — через сервис или workflow, инфраструктуру связывает DI; Eloquent-модель в Response — допустима');
    }}

    private static function infrastructureWithoutModels(): SelectorInterface
    {{
        return Selector::AllOf(
            Selector::inNamespace(self::ROOT . '\\Infrastructure'),
            Selector::NOT(Selector::classname(self::MODELS, true)),
        );
    }}
}}
""")
    return "".join(out)


EXCEPTION_RULE = "GenericExceptionRule"


def render_exception_rule(namespace: str) -> str:
    """Своё правило phpstan: встроенное исключение PHP (`\\Exception`, `\\RuntimeException`, `\\InvalidArgumentException`,
    `\\Error`…) через `new` или `throw_if`/`throw_unless(…, X::class)` в коде приложения — только исключения ядра
    (`stacks/php/conventions.md` п. 13). Запрет класса через disallowed-calls задел бы и `catch (\\Exception $e)`."""
    return f"""<?php

declare(strict_types=1);

namespace {namespace};

use PhpParser\\Node;
use PhpParser\\Node\\Expr\\ClassConstFetch;
use PhpParser\\Node\\Expr\\FuncCall;
use PhpParser\\Node\\Expr\\New_;
use PhpParser\\Node\\Name;
use PHPStan\\Analyser\\Scope;
use PHPStan\\Reflection\\ReflectionProvider;
use PHPStan\\Rules\\Rule;
use PHPStan\\Rules\\RuleErrorBuilder;

/**
 * Собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
 * Тег exception-not-domain-type: бросать только исключения ядра (*AppException), не встроенные исключения PHP.
 * Тесты и миграции не проверяются.
 *
 * @implements Rule<Node\\Expr>
 */
final class {EXCEPTION_RULE} implements Rule
{{
    public function __construct(private readonly ReflectionProvider $reflection)
    {{
    }}

    public function getNodeType(): string
    {{
        return Node\\Expr::class;
    }}

    public function processNode(Node $node, Scope $scope): array
    {{
        $file = str_replace('\\\\', '/', $scope->getFile());
        if (str_contains($file, '/tests/') || str_contains($file, '/database/migrations/')) {{
            return [];
        }}
        $name = null;
        if ($node instanceof New_ && $node->class instanceof Name) {{
            $name = $node->class;
        }} elseif ($node instanceof FuncCall && $node->name instanceof Name
            && in_array(strtolower($node->name->toString()), ['throw_if', 'throw_unless'], true)
            && isset($node->args[1]) && $node->args[1] instanceof Node\\Arg
            && $node->args[1]->value instanceof ClassConstFetch && $node->args[1]->value->class instanceof Name) {{
            $name = $node->args[1]->value->class;
        }}
        if ($name === null) {{
            return [];
        }}
        $class = ltrim($scope->resolveName($name), '\\\\');
        if (!$this->reflection->hasClass($class)) {{
            return [];
        }}
        $reflection = $this->reflection->getClass($class);
        if (!$reflection->isBuiltin() || !$reflection->implementsInterface('Throwable')) {{
            return [];
        }}

        return [
            RuleErrorBuilder::message(sprintf('\\\\%s — встроенное исключение PHP; только исключения ядра (NotFoundAppException, ValidationAppException, …) (exception-not-domain-type)', $reflection->getName()))
                ->identifier('devFramework.exceptionNotDomainType')
                ->build(),
        ];
    }}
}}
"""


STATUS_RULE = "StatusSetDirectlyRule"
RELATION_RULE = "EloquentRelationRule"
OWN_RULES = (EXCEPTION_RULE, STATUS_RULE, RELATION_RULE)

_STATUS_RULE_PHP = r"""<?php

declare(strict_types=1);

namespace __NS__;

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
"""

_RELATION_RULE_PHP = r"""<?php

declare(strict_types=1);

namespace __NS__;

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
"""


def render_status_rule(namespace: str) -> str:
    """Своё правило phpstan: запись поля состояния мимо перехода (`stacks/php/checklist.md` п. 15a, O-28)."""
    return _STATUS_RULE_PHP.replace("__NS__", namespace)


def render_relation_rule(namespace: str) -> str:
    """Своё правило phpstan: Eloquent-связь в модели (`stacks/php/conventions.md`, «Модели и внешние системы»)."""
    return _RELATION_RULE_PHP.replace("__NS__", namespace)


def render_own_rules(namespace: str) -> dict[str, str]:
    """Свои правила phpstan: имя класса → код."""
    return {EXCEPTION_RULE: render_exception_rule(namespace), STATUS_RULE: render_status_rule(namespace),
            RELATION_RULE: render_relation_rule(namespace)}


def render_neon(arch_class: str) -> str:
    return f"""# Механические правила памятки для phpstan: собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
# Нужны пакеты phpat/phpat и spaze/phpstan-disallowed-calls; каждое правило проверено заведомым нарушением.

services:
    -
        class: {arch_class}
        tags:
            - phpat.test

rules:
    # встроенное исключение PHP (new, throw_if/throw_unless) вне тестов и миграций — только исключения ядра (conventions.md п. 13)
    - {arch_class.rsplit(chr(92), 1)[0]}\\{EXCEPTION_RULE}
    # status/is_default мимо перехода: свойство модели, setAttribute(), ключ в fill()/update()/create()… вне модели и *Repository (checklist.md п. 15a)
    - {arch_class.rsplit(chr(92), 1)[0]}\\{STATUS_RULE}
    # Eloquent-связь в модели: метод возвращает Relation или вызывает $this->hasMany()/belongsTo()/… (conventions.md, «Модели и внешние системы»)
    - {arch_class.rsplit(chr(92), 1)[0]}\\{RELATION_RULE}

parameters:
    # Хранилище — только через Infrastructure (architecture/layers.md): фасад DB вне Infrastructure, миграций и тестов —
    # находка; запрещён сам фасад, т. к. вызов DB::transaction() идёт через __callStatic и запрет метода его не видит.
    # DB::transaction внутри Infrastructure (в репозитории) этим правилом не ловится — его проверяет агент.
    disallowedClasses:
        -
            class: 'Illuminate\\Support\\Facades\\DB'
            message: 'фасад DB — только в Infrastructure (репозиторий, менеджер транзакций), миграциях и тестах; транзакция — TransactionManagerInterface::run()'
            allowIn:
                - '*/Infrastructure/*'
                - '*/database/migrations/*'
                - '*/tests/*'
        -
            class: 'DB'
            message: 'фасад DB (глобальный алиас DB) — только в Infrastructure (репозиторий, менеджер транзакций), миграциях и тестах; транзакция — TransactionManagerInterface::run()'
            allowIn:
                - '*/Infrastructure/*'
                - '*/database/migrations/*'
                - '*/tests/*'
        -
            class: 'Illuminate\\Support\\Facades\\Auth'
            message: 'текущий пользователь — $request->user() в контроллере, в домен и workflow — значением (checklist.md п. 14)'
            allowExceptIn:
                - '*/Domains/*'
                - '*/Workflows/*'
        -
            class: 'Auth'
            message: 'фасад Auth (глобальный алиас Auth): текущий пользователь — $request->user() в контроллере, в домен и workflow — значением (checklist.md п. 14)'
            allowExceptIn:
                - '*/Domains/*'
                - '*/Workflows/*'
    disallowedFunctionCalls:
        -
            function:
                - 'app()'
                - 'resolve()'
            message: 'зависимость — через конструктор, не из контейнера в бизнес-коде'
            allowIn:
                - '*ServiceProvider.php'
                - '*/definitions.php'
                - '*/Providers/*'
                - '*/config/*'
                - '*/routes/*'
                - '*/commands.php'
                - '*/database/migrations/*'
                - '*/tests/*'
        -
            function:
                - 'auth()'
            message: 'текущий пользователь — $request->user() в контроллере, в домен и workflow — значением (checklist.md п. 14)'
            allowExceptIn:
                - '*/Domains/*'
                - '*/Workflows/*'
        -
            function:
                - 'env()'
            message: 'env() — только в config/*.php и config.php модуля: после config:cache вне конфигов он возвращает null; в коде — config() (checklist.md п. 51)'
            allowIn:
                - '*/config/*'
                - '*/config.php'
        -
            function:
                - 'dd()'
                - 'dump()'
                - 'var_dump()'
            message: 'отладочный вывод в коде — убрать; диагностика — логгер (checklist.md п. 52)'
"""
