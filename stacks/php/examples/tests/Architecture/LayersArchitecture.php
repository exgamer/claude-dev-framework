<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Selector\SelectorInterface;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Границы слоёв и доменов подпроекта — проверяет phpstan (phpat); собран tools/lint/php_rules.py фреймворка, правка — в генераторе.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class LayersArchitecture
{
    private const ROOT = 'App\ParkingApp';

    /** Eloquent-модель — доменная модель (P-4): лежит в Infrastructure, но домен и Response её знают. */
    private const MODELS = '/^App\\\\ParkingApp\\\\Infrastructure\\\\.+\\\\Models\\\\/';

    public function test_domain_does_not_know_outer_layers(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Domains'))
            ->shouldNotDependOn()
            ->classes(
                self::infrastructureWithoutModels(),
                Selector::inNamespace(self::ROOT . '\Entrypoints'),
                Selector::inNamespace(self::ROOT . '\Workflows'),
            )
            ->because('домен не знает инфраструктуру (кроме Eloquent-модели, P-4), точки входа и workflow — порт в домене, адаптер в Infrastructure');
    }

    public function test_billing_does_not_know_other_domains(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Domains\Billing'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::ROOT . '\Domains\Catalog'),
            )
            ->because('чужой домен — через workflow или Common');
    }

    public function test_catalog_does_not_know_other_domains(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Domains\Catalog'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::ROOT . '\Domains\Billing'),
            )
            ->because('чужой домен — через workflow или Common');
    }

    public function test_billing_outbox_tariff_events_does_not_know_other_modules(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Domains\Billing\Modules\OutboxTariffEvents'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::ROOT . '\Domains\Billing\Modules\Tariffs'),
            )
            ->because('соседний модуль домена Billing — интерфейс у потребителя, DI связывает его с соседом (O-6); общее модулей — Domains/Billing/Common');
    }

    public function test_billing_tariffs_does_not_know_other_modules(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Domains\Billing\Modules\Tariffs'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::ROOT . '\Domains\Billing\Modules\OutboxTariffEvents'),
            )
            ->because('соседний модуль домена Billing — интерфейс у потребителя, DI связывает его с соседом (O-6); общее модулей — Domains/Billing/Common');
    }

    public function test_billing_common_does_not_know_modules(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Domains\Billing\Common'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::ROOT . '\Domains\Billing\Modules\OutboxTariffEvents'),
                Selector::inNamespace(self::ROOT . '\Domains\Billing\Modules\Tariffs'),
            )
            ->because('Common домена Billing — общее его модулей: модули его знают, он их — нет');
    }

    public function test_common_has_no_layer_dependencies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Common'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::ROOT . '\Core'),
                Selector::inNamespace(self::ROOT . '\Domains'),
                Selector::inNamespace(self::ROOT . '\Entrypoints'),
                Selector::inNamespace(self::ROOT . '\Infrastructure'),
                Selector::inNamespace(self::ROOT . '\Workflows'),
            )
            ->because('Common — общий словарь без зависимостей от слоёв');
    }

    public function test_workflow_does_not_call_workflow(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname('/^App\\\\ParkingApp\\\\Workflows\\\\.+Workflow$/', true))
            ->shouldNotDependOn()
            ->classes(Selector::classname('/^App\\\\ParkingApp\\\\Workflows\\\\.+Workflow$/', true))
            ->because('workflow не вызывает другой workflow — общее выносится в домены');
    }

    public function test_entrypoint_does_not_know_infrastructure(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT . '\Entrypoints'))
            ->shouldNotDependOn()
            ->classes(self::infrastructureWithoutModels())
            ->because('точка входа — через сервис или workflow, инфраструктуру связывает DI; Eloquent-модель в Response — допустима (P-4)');
    }

    private static function infrastructureWithoutModels(): SelectorInterface
    {
        return Selector::AllOf(
            Selector::inNamespace(self::ROOT . '\Infrastructure'),
            Selector::NOT(Selector::classname(self::MODELS, true)),
        );
    }
}
