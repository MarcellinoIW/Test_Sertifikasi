<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel users (admin & penyewa).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->char('nik', 16)->nullable()->unique()->comment('NIK KTP, null untuk admin');
            $table->string('name');
            $table->string('phone', 15)->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', array_column(UserRole::cases(), 'value'))->default(UserRole::Tenant->value);
            $table->enum('status', array_column(UserStatus::cases(), 'value'))->default(UserStatus::Pending->value);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['role', 'status']);
        });
    }

    /**
     * Menghapus tabel users.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
