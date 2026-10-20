<?php

declare(strict_types=1);

namespace App\ParkingApp\Infrastructure\Postgres\Billing\OutboxTariffEvents\Models;

use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventRoutingKeyEnum;
use App\ParkingApp\Domains\Billing\Modules\OutboxTariffEvents\Enums\OutboxTariffEventStatusEnum;
use Carbon\Carbon;
use MPS\Core\Enums\CurrencyEnum;
use MPS\Core\Models\Model;

/**
 * Событие о тарифе для отправки в RabbitMQ (outbox)
 *
 * @property int $id                                      Уходит в сообщение как event_id
 * @property int $tariff_id
 * @property int $parking_id
 * @property string $name                                 Название тарифа на момент события
 * @property CurrencyEnum $currency
 * @property int $price                                   Цена часа в минимальных единицах валюты
 * @property int|null $grace_minutes                      Льготный период, минуты
 * @property OutboxTariffEventRoutingKeyEnum $routing_key Routing key публикации, он же тип события
 * @property OutboxTariffEventStatusEnum $status
 * @property int $attempts                                Неудачных попыток отправки
 * @property Carbon|null $published_at                    Когда брокер принял событие
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 *
 * @author Olzhas Kulzhambekov <olzhas.k@mpinnovations.kz>
 */
class OutboxTariffEvent extends Model
{
    protected $table = 'parking.outbox_tariff_events';

    protected $fillable = [
        'tariff_id',
        'parking_id',
        'name',
        'currency',
        'price',
        'grace_minutes',
        'routing_key',
        'status',
        'attempts',
    ];

    protected $casts = [
        'tariff_id' => 'integer',
        'parking_id' => 'integer',
        'price' => 'integer',
        'grace_minutes' => 'integer',
        'attempts' => 'integer',
        'currency' => CurrencyEnum::class,
        'routing_key' => OutboxTariffEventRoutingKeyEnum::class,
        'status' => OutboxTariffEventStatusEnum::class,
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
