"""Шаблоны правил PHP для phpstan (phpat + phpstan-disallowed-calls): класс архитектурных правил и neon.

Эталон (`stacks/php/examples/tests/Architecture/LayersArchitecture.php`, `phpstan-rules.neon`) — вывод этих функций
для `App\\ParkingApp` и доменов `Billing`, `Catalog`; сверка — `tools/check/lint-rules.py`.
"""
import re

LAYERS = ("Core", "Domains", "Entrypoints", "Infrastructure", "Workflows")


def snake(name: str) -> str:
    return re.sub(r"(?<!^)(?=[A-Z])", "_", name).lower()


def regex_root(root: str) -> str:
    """App\\ParkingApp → App\\\\\\\\ParkingApp для PHP-строки с регулярным выражением в одинарных кавычках."""
    return root.replace("\\", "\\\\\\\\")


def render_arch(namespace: str, root: str, domains: list[str]) -> str:
    rr = regex_root(root)
    # Domains/Common — общий модуль проекта внутри Domains (старая раскладка), не домен: его знает любой домен,
    # а сам он не знает ни доменов, ни внешних слоёв
    shared = "Common" in domains
    domains = [d for d in domains if d != "Common"]
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
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class LayersArchitecture
{{
    private const ROOT = '{root}';

    /** Eloquent-модель — доменная модель (P-4): лежит в Infrastructure, но домен и Response её знают. */
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
            ->because('домен не знает инфраструктуру (кроме Eloquent-модели, P-4), точки входа и workflow — порт в домене, адаптер в Infrastructure');
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
    if shared and domains:
        targets = "\n".join(f"                Selector::inNamespace(self::ROOT . '\\Domains\\{o}')," for o in domains)
        out.append(f"""
    public function test_domains_common_has_no_domain_dependencies(): Rule
    {{
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\\Domains\\Common'))
            ->shouldNotDependOn()
            ->classes(
{targets}
            )
            ->because('Domains/Common — общий модуль проекта: его знают домены, сам он доменов не знает — то же правило, что Common/ (common-with-logic)');
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
            ->because('точка входа — через сервис или workflow, инфраструктуру связывает DI; Eloquent-модель в Response — допустима (P-4)');
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
    disallowedFunctionCalls:
        -
            function:
                - 'app()'
                - 'resolve()'
            message: 'зависимость — через конструктор, не из контейнера в бизнес-коде'
            allowIn:
                - '*/ServiceProvider.php'
                - '*/Providers/*'
                - '*/config/*'
                - '*/routes/*'
                - '*/commands.php'
                - '*/database/migrations/*'
                - '*/tests/*'
"""
