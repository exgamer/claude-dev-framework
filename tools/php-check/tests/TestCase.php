<?php

declare(strict_types=1);

namespace Tests;

use App\ParkingApp\Core\Http\Middleware\VerifySignatureMiddleware;
use App\ParkingApp\ServiceProvider;
use MPS\Utils\Components\Logger\LogServiceProvider;
use MPS\Utils\Http\Middleware\ApiResponseMiddleware;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Хост для тестов эталона: то, что в приложении дают bootstrap/app.php и config/ (stacks/php/examples/README.md, «часть хоста»).
 */
abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LogServiceProvider::class, ServiceProvider::class];
    }

    protected function defineRoutes($router): void
    {
        $router->aliasMiddleware('verify.signature', VerifySignatureMiddleware::class);
        $router->aliasMiddleware('api.response', ApiResponseMiddleware::class);
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('mps-utils', require __DIR__ . '/../config/mps-utils.php');
        $config->set('parking-app', require __DIR__ . '/../config/parking-app.php');
        $config->set('services', array_merge($config->get('services', []), require __DIR__ . '/../config/services.php'));
    }
}
