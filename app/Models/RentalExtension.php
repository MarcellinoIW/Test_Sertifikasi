<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

// model untuk data perpanjangan sewa
class RentalExtension extends Model
{
    use HasFactory;

    // kolom yang boleh diisi
    protected $fillable = [
        'rental_id',
        'admin_id',
        'old_end_date',
        'new_end_date',
        'additional_days',
        'monthly_blocks',
        'daily_days',
        'cost',
        'payment_proof',
    ];

    // function untuk mengatur tipe data kolom
    protected function casts(): array
    {
        return [
            'old_end_date' => 'date',
            'new_end_date' => 'date',
            'additional_days' => 'integer',
            'monthly_blocks' => 'integer',
            'daily_days' => 'integer',
            'cost' => 'decimal:2',
        ];
    }

    // function relasi perpanjangan ke data sewa
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    // function relasi perpanjangan ke admin yang memproses
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // function untuk mengambil link gambar bukti bayar
    public function paymentProofUrl(): string
    {
        return asset('storage/'.$this->payment_proof);
    }

    // function untuk mengecek file bukti bayar ada atau tidak
    public function hasPaymentProofFile(): bool
    {
        return filled($this->payment_proof) && Storage::disk('public')->exists($this->payment_proof);
    }
}
