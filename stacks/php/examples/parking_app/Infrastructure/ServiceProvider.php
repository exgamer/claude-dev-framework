<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Агрегатор провайдеров Infrastructure: биндинги репозиториев, адаптеров, миграции.
 *
 * @author Имя Фамилия <name@example.com>
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * @var list<class-string<BaseServiceProvider>>
     */
    private array $providers = [
        Postgres\Common\Providers\ServiceProvider::class,
        Postgres\Billing\Providers\ServiceProvider::class,
        Cache\Common\Providers\ServiceProvider::class,
        Cache\Billing\Providers\ServiceProvider::class,
        Http\CityParking\Providers\ServiceProvider::class,
        Http\PartnerBilling\Providers\ServiceProvider::class,
        RabbitMQ\Billing\Providers\ServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
