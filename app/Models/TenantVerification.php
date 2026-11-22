<?php

namespace App\Models;

use App\Enums\VerificationAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// model untuk riwayat verifikasi penyewa
class TenantVerification extends Model
{
    // kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'admin_id',
        'action',
        'created_at',
    ];

    // function untuk mengatur tipe data kolom
    protected function casts(): array
    {
        return [
            'action' => VerificationAction::class,
        ];
    }

    // function relasi riwayat verifikasi ke penyewa
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // function relasi riwayat verifikasi ke admin yang memproses
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
