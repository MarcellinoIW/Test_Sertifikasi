<?php

use App\Enums\RentalType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // function untuk membuat tabel rentals
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('garage_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->enum('rental_type', RentalType::values());
            $table->unsignedSmallInteger('days');
            $table->date('start_date');
            $table->date('end_date')->comment('Eksklusif: garasi bisa disewa lagi pada tanggal ini');
            $table->decimal('daily_price', 12, 2)->comment('Snapshot harga harian');
            $table->decimal('rent_cost', 14, 2);
            $table->decimal('battery_remaining_kwh', 8, 2)->nullable();
            $table->decimal('charged_kwh', 8, 2)->nullable();
            $table->decimal('electricity_price_per_kwh', 12, 2)->nullable()->comment('Snapshot harga per kWh');
            $table->decimal('electricity_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2);
            $table->string('payment_proof');
            $table->timestamps();

            $table->index(['garage_id', 'start_date', 'end_date']);
            $table->index('start_date');
        });
    }

    // function untuk menghapus tabel rentals
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
