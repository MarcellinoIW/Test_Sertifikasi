<?php

namespace App\Models;

use App\Enums\VehicleType;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kendaraan milik penyewa yang dititipkan di garasi.
 *
 * @property int $id
 * @property int $user_id
 * @property VehicleType $type
 * @property string|null $plate_number
 * @property string $brand_model
 * @property string|null $battery_capacity_kwh
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'plate_number',
        'brand_model',
        'battery_capacity_kwh',
    ];

    /**
     * Konversi tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'battery_capacity_kwh' => 'decimal:2',
        ];
    }

    /**
     * Pemilik kendaraan.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sewa yang menggunakan kendaraan ini.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Teks ringkas kendaraan untuk tampilan, misal "Mobil - Toyota Avanza (B 1234 CD)".
     *
     * @return string
     */
    public function displayName(): string
    {
        $plate = $this->plate_number ? " ({$this->plate_number})" : '';

        return "{$this->type->label()} - {$this->brand_model}{$plate}";
    }
}
