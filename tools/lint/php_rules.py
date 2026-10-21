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


def domain_modules(dom_dir: Path) -> dict[str, list[str]]:
    """Модули каждого домена — каталоги `Domains/{Домен}/Modules/*`; `Common` в списке — у домена есть `Domains/{Домен}/Common/`."""
    out = {}
    for d in sorted(p for p in dom_dir.iterdir() if p.is_dir()) if dom_dir.is_dir() else []:
        mods = d / "Modules"
        names = sorted(p.name for p in mods.iterdir() if p.is_dir()) if mods.is_dir() else []
        out[d.name] = names + (["Common"] if (d / "Common").is_dir() else [])
    return out


def render_arch(namespace: str, root: str, domains: list[str], modules: dict[str, list[str]] | None = None) -> str:
    modules = modules or {}
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
                   else f"соседний модуль домена {d} — интерфейс у потребителя, DI связывает его с соседом (O-6); общее модулей — Domains/{d}/Common")
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
