<?php

declare(strict_types=1);

namespace App\ParkingApp\Core\Http\Middleware;

use App\ParkingApp\Core\Enums\RequestHeaderEnum;
use App\ParkingApp\Core\Helpers\SignatureHelper;
use App\ParkingApp\Core\Http\Signature\SignatureNonceRepositoryInterface;
use App\ParkingApp\Core\Http\Signature\SignedMessage;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MPS\Core\Exceptions\InvalidConfigurationAppException;
use MPS\Core\Exceptions\UnAuthorizeAppException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Подпись машинного вызова (алиас verify.signature, контекст Integration).
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
final class VerifySignatureMiddleware
{
    private readonly string $secret;

    private readonly int $toleranceSeconds;

    /**
     * @throws InvalidConfigurationAppException
     */
    public function __construct(
        private readonly SignatureNonceRepositoryInterface $nonceRepository,
        Config $config,
    ) {
        $secret = $config->get('parking-app.integration_signature.secret');

        if (! is_string($secret) || $secret === '') {
            throw new InvalidConfigurationAppException('Не задан INTEGRATION_SIGNATURE_SECRET');
        }

        $toleranceSeconds = $config->get('parking-app.integration_signature.tolerance_seconds');

        if (! is_numeric($toleranceSeconds) || (int) $toleranceSeconds <= 0) {
            throw new InvalidConfigurationAppException('Не задан INTEGRATION_SIGNATURE_TOLERANCE_SECONDS');
        }

        $this->secret = $secret;
        $this->toleranceSeconds = (int) $toleranceSeconds;
    }

    /**
     * HMAC метода, пути с query и тела, окно времени, затем одноразовый nonce. Не принята — 401 без причины в ответе.
     *
     * @param Closure(Request): Response $next
     *
     * @throws UnAuthorizeAppException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header(RequestHeaderEnum::SIGNATURE->value);
        $signature = SignatureHelper::parse($header);

        if ($signature === null) {
            throw new UnAuthorizeAppException('Подпись недействительна');
        }

        $now = Carbon::now()->getTimestamp();

        if (abs($now - $signature->timestamp) > $this->toleranceSeconds) {
            throw new UnAuthorizeAppException('Подпись недействительна');
        }

        $message = new SignedMessage($request->getMethod(), $request->getRequestUri(), $request->getContent());

        if (! SignatureHelper::verify($this->secret, $signature, $message)) {
            throw new UnAuthorizeAppException('Подпись недействительна');
        }

        // nonce занимается после проверки подписи: неподписанный запрос чужой nonce не займёт
        if (! $this->nonceRepository->remember($signature->nonce)) {
            throw new UnAuthorizeAppException('Подпись недействительна');
        }

        return $next($request);
    }
}
