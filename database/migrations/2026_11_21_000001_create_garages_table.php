<?php

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel garages.
     */
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique()->comment('Prefix tipe + 3 digit, misal S001');
            $table->enum('type', GarageType::values());
            $table->decimal('daily_price', 12, 2);
            $table->decimal('electricity_price_per_kwh', 12, 2)->nullable()->comment('Hanya untuk tipe electric');
            $table->enum('status', array_column(GarageStatus::cases(), 'value'))->default(GarageStatus::Available->value);
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    /**
     * Menghapus tabel garages.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
