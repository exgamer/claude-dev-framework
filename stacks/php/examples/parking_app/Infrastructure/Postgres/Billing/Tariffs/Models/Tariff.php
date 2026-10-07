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
 * @property int $id                      PK
 * @property int $parking_id              ID парковки
 * @property string $name                 Название тарифа
 * @property CurrencyEnum $currency       Валюта
 * @property bool $is_default             Тариф по умолчанию, у парковки один
 * @property TariffStatusEnum $status     Статус
 * @property int|null $grace_minutes      Льготный период (минуты)
 * @property Carbon $created_at           Дата создания
 * @property Carbon|null $updated_at      Дата обновления
 * @property Carbon|null $deleted_at      Soft delete
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
        'is_default',
        'status',
        'grace_minutes',
    ];

    protected $casts = [
        'parking_id' => 'integer',
        'is_default' => 'boolean',
        'grace_minutes' => 'integer',
        'currency' => CurrencyEnum::class,
        'status' => TariffStatusEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
