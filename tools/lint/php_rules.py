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


def render_neon(arch_class: str) -> str:
    return f"""# Механические правила памятки для phpstan: собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
# Нужны пакеты phpat/phpat и spaze/phpstan-disallowed-calls; каждое правило проверено заведомым нарушением.

services:
    -
        class: {arch_class}
        tags:
            - phpat.test

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
