<?php

namespace App\Models;

use Database\Factories\RentalExtensionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Riwayat perpanjangan sewa yang diproses oleh admin.
 *
 * @property int $id
 * @property int $rental_id
 * @property int $admin_id
 * @property Carbon $old_end_date
 * @property Carbon $new_end_date
 * @property int $additional_days
 * @property int $monthly_blocks
 * @property int $daily_days
 * @property string $cost
 * @property string $payment_proof
 * @property Carbon $created_at
 */
class RentalExtension extends Model
{
    /** @use HasFactory<RentalExtensionFactory> */
    use HasFactory;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var list<string>
     */
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

    /**
     * Konversi tipe atribut.
     *
     * @return array<string, string>
     */
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

    /**
     * URL publik gambar bukti bayar perpanjangan.
     *
     * @return string
     */
    public function paymentProofUrl(): string
    {
        return asset('storage/'.$this->payment_proof);
    }

    /**
     * Rental yang diperpanjang.
     *
     * @return BelongsTo<Rental, $this>
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Admin yang memproses perpanjangan.
     *
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
