<?php

declare(strict_types=1);

namespace App\ParkingApp;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use MPS\Utils\Components\HealthCheck\ServiceProvider as HealthCheckServiceProvider;

/**
 * Корневой провайдер подпроекта: регистрирует провайдеры доменов, инфраструктуры и точек входа.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Биндинги сервисов доменов.
     *
     * @var list<class-string<BaseServiceProvider>>
     */
    private array $domainProviders = [
        Domains\Billing\ServiceProvider::class,
    ];

    /**
     * Биндинги репозиториев и адаптеров, миграции.
     *
     * @var list<class-string<BaseServiceProvider>>
     */
    private array $infrastructureProviders = [
        Infrastructure\ServiceProvider::class,
    ];

    /**
     * Маршруты и команды точек входа, по контекстам.
     *
     * @var list<class-string<BaseServiceProvider>>
     */
    private array $entrypointProviders = [
        Entrypoints\Admin\Billing\ServiceProvider::class,
        Entrypoints\Integration\Billing\ServiceProvider::class,
        Entrypoints\Console\Billing\ServiceProvider::class,
    ];

    /**
     * Готовые компоненты mps/utils: liveness/readiness (`/health/*`, настройки — `config/mps-utils.php`).
     *
     * @var list<class-string<BaseServiceProvider>>
     */
    private array $utilsProviders = [
        HealthCheckServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->domainProviders as $provider) {
            $this->app->register($provider);
        }

        foreach ($this->infrastructureProviders as $provider) {
            $this->app->register($provider);
        }

        foreach ($this->entrypointProviders as $provider) {
            $this->app->register($provider);
        }

        foreach ($this->utilsProviders as $provider) {
            $this->app->register($provider);
        }
    }
}
