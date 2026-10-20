<?php

declare(strict_types=1);

use App\ParkingApp\Domains\Billing\Modules\Tariffs\Enums\TariffStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MPS\Core\Enums\CurrencyEnum;

return new class () extends Migration {
    private string $tableName = 'parking.tariffs';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parking_id')->comment('ID парковки');
            $table->string('name')->comment('Название тарифа');
            $table->string('currency', 3)->default(CurrencyEnum::KZT->value)->comment('Валюта');
            $table->unsignedBigInteger('price')->comment('Цена часа в минимальных единицах валюты');
            $table->boolean('is_default')->default(false)->comment('Тариф по умолчанию');
            $table->string('status', 20)
                ->default(TariffStatusEnum::ACTIVE->value)
                ->comment('Статус: ' . TariffStatusEnum::ACTIVE->value . ', ' . TariffStatusEnum::ARCHIVED->value);
            $table->unsignedSmallInteger('grace_minutes')->nullable()->comment('Льготный период (минуты)');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->index('parking_id');

            $table->foreign('parking_id')
                ->references('id')
                ->on('parking.parkings')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });

        DB::statement("CREATE TRIGGER on_update_current_timestamp BEFORE UPDATE ON {$this->tableName} FOR EACH ROW EXECUTE PROCEDURE parking.on_update_current_timestamp()");
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
