<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\Tariffs\Models;

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Models\Model;

/**
 * Строка parking.tariffs: мягкое удаление, статус и валюта — enum, у парковки один тариф по умолчанию (частичный уникальный индекс).
 * Состояние (status, is_default, scheduled_at) не в $fillable: начальное задаёт initState(), смену — Command перехода
 * после проверки can*() этой модели и условный UPDATE метода репозитория этого перехода.
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
        'grace_minutes',
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

    /**
     * Начальное состояние нового тарифа: с датой активации — запланированный (действующим и по умолчанию его сделает
     * ActivatePlannedTariffJob в эту дату), без неё — сразу действующий; по умолчанию не создаётся — только переходом.
     */
    public function initState(?Carbon $scheduledAt): self
    {
        $this->status = $scheduledAt !== null ? TariffStatusEnum::SCHEDULED : TariffStatusEnum::ACTIVE;
        $this->is_default = false;
        $this->scheduled_at = $scheduledAt;

        return $this;
    }

    /**
     * Активировать можно только запланированный: действующий уже активирован, архивный отключён вместе с парковкой.
     */
    public function canActivate(): bool
    {
        return $this->status === TariffStatusEnum::SCHEDULED;
    }

    /**
     * Тарифом по умолчанию назначается только действующий: запланированный станет им сам в дату активации, архивный — нет.
     */
    public function canBeDefault(): bool
    {
        return $this->status === TariffStatusEnum::ACTIVE;
    }

    /**
     * В архив — действующий и запланированный; тот же набор — в условии UPDATE archiveNotArchivedByParkingId.
     */
    public function canArchive(): bool
    {
        return in_array($this->status, TariffStatusEnum::notArchived(), true);
    }
}
