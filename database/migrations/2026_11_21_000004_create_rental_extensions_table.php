<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // function untuk membuat tabel rental_extensions
    public function up(): void
    {
        Schema::create('rental_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->date('old_end_date');
            $table->date('new_end_date');
            $table->unsignedInteger('additional_days');
            $table->unsignedInteger('monthly_blocks');
            $table->unsignedInteger('daily_days');
            $table->decimal('cost', 14, 2);
            $table->string('payment_proof');
            $table->timestamps();

            $table->index('created_at');
        });
    }

    // function untuk menghapus tabel rental_extensions
    public function down(): void
    {
        Schema::dropIfExists('rental_extensions');
    }
};
