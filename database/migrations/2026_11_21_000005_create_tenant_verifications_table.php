<?php

use App\Enums\VerificationAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // function untuk membuat tabel tenant_verifications
    public function up(): void
    {
        Schema::create('tenant_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->comment('Null untuk aksi oleh pendaftar sendiri')
                ->constrained('users')->nullOnDelete();
            $table->enum('action', VerificationAction::values());
            $table->timestamps();

            $table->index(['user_id', 'action']);
        });
    }

    // function untuk menghapus tabel tenant_verifications
    public function down(): void
    {
        Schema::dropIfExists('tenant_verifications');
    }
};
