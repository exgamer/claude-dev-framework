<?php

declare(strict_types=1);

namespace Tests\Unit\ParkingApp\Core\Http\Middleware;

use App\ParkingApp\Core\Http\Middleware\ResolveAdminParkings;
use App\ParkingApp\Core\Http\RequestHelper;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use MPS\Core\Exceptions\AccessDeniedAppException;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\UnAuthorizeAppException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Парковки администратора — в атрибуты запроса; чужой parking_id из query или тела — 403.
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class ResolveAdminParkingsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const OWN_PARKING_ID = 15;

    private const OTHER_PARKING_ID = 16;

    public function test_admin_parkings_are_put_into_request_attributes(): void
    {
        $request = $this->request('/parking/admin/tariffs', admin: $this->admin([self::OWN_PARKING_ID]));

        $this->handle($request);

        $this->assertSame([self::OWN_PARKING_ID], RequestHelper::getParkingIds($request));
    }

    public function test_super_admin_is_unrestricted(): void
    {
        $request = $this->request('/parking/admin/tariffs?parking_id=' . self::OTHER_PARKING_ID, admin: $this->admin([], isSuperAdmin: true));

        $this->handle($request);

        $this->assertNull(RequestHelper::getParkingIds($request));
    }

    public function test_admin_without_parkings_gets_empty_scope_not_unrestricted(): void
    {
        $request = $this->request('/parking/admin/tariffs', admin: $this->admin([]));

        $this->handle($request);

        $this->assertSame([], RequestHelper::getParkingIds($request));
    }

    public function test_other_parking_in_query_is_forbidden(): void
    {
        $request = $this->request('/parking/admin/tariffs?parking_id=' . self::OTHER_PARKING_ID, admin: $this->admin([self::OWN_PARKING_ID]));

        $this->expectException(AccessDeniedAppException::class);

        $this->handle($request);
    }

    public function test_other_parking_in_body_is_forbidden(): void
    {
        $request = $this->request(
            '/parking/admin/tariffs',
            admin: $this->admin([self::OWN_PARKING_ID]),
            method: 'POST',
            body: ['parking_id' => self::OTHER_PARKING_ID, 'name' => 'Стандарт'],
        );

        $this->expectException(AccessDeniedAppException::class);

        $this->handle($request);
    }

    public function test_request_without_admin_is_unauthorized(): void
    {
        $this->expectException(UnAuthorizeAppException::class);

        $this->handle($this->request('/parking/admin/tariffs', admin: null));
    }

    public function test_route_without_middleware_is_configuration_error(): void
    {
        $this->expectException(InvalidConfigurationAppException::class);

        RequestHelper::getParkingIds(Request::create('/parking/admin/tariffs'));
    }

    private function handle(Request $request): void
    {
        $middleware = new ResolveAdminParkings();
        $middleware->handle($request, static fn (): Response => new Response());
    }

    /**
     * @param ?array<string, mixed> $body
     */
    private function request(string $uri, ?object $admin, string $method = 'GET', ?array $body = null): Request
    {
        $request = $body === null
            ? Request::create($uri, $method)
            : Request::create($uri, $method, server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($body, JSON_THROW_ON_ERROR));
        $request->setUserResolver(fn (): ?object => $admin);

        return $request;
    }

    /**
     * @param list<int> $parkingIds
     */
    private function admin(array $parkingIds, bool $isSuperAdmin = false): object
    {
        $admin = Mockery::mock();
        $admin->shouldReceive('isSuperAdmin')->andReturn($isSuperAdmin);
        $admin->shouldReceive('getParkingIds')->andReturn($parkingIds);

        return $admin;
    }
}
