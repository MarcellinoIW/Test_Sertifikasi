<?php

use App\Enums\VehicleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel vehicles (kendaraan milik penyewa).
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', VehicleType::values());
            $table->string('plate_number', 15)->nullable()->comment('Null untuk sepeda');
            $table->string('brand_model', 100);
            $table->decimal('battery_capacity_kwh', 8, 2)->nullable()->comment('Hanya untuk mobil listrik');
            $table->timestamps();

            $table->index('plate_number');
        });
    }

    /**
     * Menghapus tabel vehicles.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
