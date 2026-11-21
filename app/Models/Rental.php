<?php

namespace App\Models;

use App\Enums\RentalType;
use Carbon\CarbonInterface;
use Database\Factories\RentalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Transaksi sewa garasi. Rental aktif pada tanggal D jika
 * start_date <= D < end_date (end_date bersifat eksklusif).
 *
 * @property int $id
 * @property int $user_id
 * @property int $garage_id
 * @property int $vehicle_id
 * @property RentalType $rental_type
 * @property int $days
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string $daily_price
 * @property string $rent_cost
 * @property string|null $battery_remaining_kwh
 * @property string|null $charged_kwh
 * @property string|null $electricity_price_per_kwh
 * @property string $electricity_cost
 * @property string $total_cost
 * @property string $payment_proof
 */
class Rental extends Model
{
    /** @use HasFactory<RentalFactory> */
    use HasFactory;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'garage_id',
        'vehicle_id',
        'rental_type',
        'days',
        'start_date',
        'end_date',
        'daily_price',
        'rent_cost',
        'battery_remaining_kwh',
        'charged_kwh',
        'electricity_price_per_kwh',
        'electricity_cost',
        'total_cost',
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
            'rental_type' => RentalType::class,
            'days' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'daily_price' => 'decimal:2',
            'rent_cost' => 'decimal:2',
            'battery_remaining_kwh' => 'decimal:2',
            'charged_kwh' => 'decimal:2',
            'electricity_price_per_kwh' => 'decimal:2',
            'electricity_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    /**
     * Penyewa pemilik rental.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Garasi yang disewa.
     *
     * @return BelongsTo<Garage, $this>
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Kendaraan yang dititipkan.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Riwayat perpanjangan rental ini.
     *
     * @return HasMany<RentalExtension, $this>
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    /**
     * Scope: rental yang aktif pada tanggal tertentu (start_date <= D < end_date).
     *
     * @param  Builder<Rental>  $query
     * @param  CarbonInterface  $date  Tanggal acuan.
     * @return Builder<Rental>
     */
    public function scopeActiveOn(Builder $query, CarbonInterface $date): Builder
    {
        $day = $date->toDateString();

        return $query->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>', $day);
    }

    /**
     * Apakah rental aktif pada tanggal tertentu.
     *
     * @param  CarbonInterface  $date  Tanggal acuan.
     * @return bool
     */
    public function isActiveOn(CarbonInterface $date): bool
    {
        $day = $date->copy()->startOfDay();

        return $this->start_date->lte($day) && $this->end_date->gt($day);
    }

    /**
     * URL publik gambar bukti pelunasan.
     *
     * @return string
     */
    public function paymentProofUrl(): string
    {
        return asset('storage/'.$this->payment_proof);
    }

    /**
     * Total yang dibayar penyewa: biaya sewa awal + listrik + seluruh perpanjangan.
     *
     * @return float
     */
    public function grandTotal(): float
    {
        $extensions = $this->relationLoaded('extensions')
            ? $this->extensions->sum('cost')
            : $this->extensions()->sum('cost');

        return (float) $this->total_cost + (float) $extensions;
    }
}
