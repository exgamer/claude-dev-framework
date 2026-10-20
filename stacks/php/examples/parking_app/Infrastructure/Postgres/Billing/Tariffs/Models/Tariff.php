<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Models\Model;

/**
 * Тариф парковки
 *
 * @property int $id
 * @property int $parking_id
 * @property string $name
 * @property CurrencyEnum $currency
 * @property int $price                   Цена часа в минимальных единицах валюты
 * @property bool $is_default             Тариф по умолчанию, у парковки один
 * @property TariffStatusEnum $status
 * @property int|null $grace_minutes      Льготный период, минуты
 * @property Carbon|null $scheduled_at    Дата активации запланированного тарифа
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class Tariff extends Model
{
    use SoftDeletes;

    protected $table = 'parking.tariffs';

    protected $fillable = [
        'parking_id',
        'name',
        'currency',
        'price',
        'is_default',
        'status',
        'grace_minutes',
        'scheduled_at',
    ];

    protected $casts = [
        'parking_id' => 'integer',
        'price' => 'integer',
        'is_default' => 'boolean',
        'grace_minutes' => 'integer',
        'currency' => CurrencyEnum::class,
        'status' => TariffStatusEnum::class,
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
