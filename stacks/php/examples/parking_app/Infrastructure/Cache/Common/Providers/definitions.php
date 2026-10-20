<?php

declare(strict_types=1);

use App\ParkingApp\Core\Http\Signature\SignatureNonceRepositoryInterface;
use App\ParkingApp\Infrastructure\Cache\Common\Signatures\Repositories\SignatureNonceRepository;

return [
    [
        'abstract' => SignatureNonceRepositoryInterface::class,
        'concrete' => SignatureNonceRepository::class,
    ],
];
