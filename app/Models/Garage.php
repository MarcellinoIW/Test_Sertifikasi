<?php

namespace App\Models;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use Carbon\CarbonInterface;
use Database\Factories\GarageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unit garasi yang dapat disewa. Satu garasi hanya untuk satu kendaraan.
 *
 * @property int $id
 * @property string $code
 * @property GarageType $type
 * @property string $daily_price
 * @property string|null $electricity_price_per_kwh
 * @property GarageStatus $status
 */
class Garage extends Model
{
    /** @use HasFactory<GarageFactory> */
    use HasFactory;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'type',
        'daily_price',
        'electricity_price_per_kwh',
        'status',
    ];

    /**
     * Konversi tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GarageType::class,
            'status' => GarageStatus::class,
            'daily_price' => 'decimal:2',
            'electricity_price_per_kwh' => 'decimal:2',
        ];
    }

    /**
     * Seluruh sewa yang pernah terjadi pada garasi ini.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Scope: filter berdasarkan tipe garasi.
     *
     * @param  Builder<Garage>  $query
     * @param  GarageType|string  $type  Tipe garasi (enum atau nilai string-nya).
     * @return Builder<Garage>
     */
    public function scopeOfType(Builder $query, GarageType|string $type): Builder
    {
        return $query->where('type', $type instanceof GarageType ? $type->value : $type);
    }

    /**
     * Scope: garasi yang tidak memiliki rental aktif pada tanggal tertentu.
     *
     * @param  Builder<Garage>  $query
     * @param  CarbonInterface  $date  Tanggal acuan.
     * @return Builder<Garage>
     */
    public function scopeAvailableOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDoesntHave('rentals', fn (Builder $rental) => $rental->activeOn($date));
    }

    /**
     * Scope: garasi yang memiliki rental aktif pada tanggal tertentu.
     *
     * @param  Builder<Garage>  $query
     * @param  CarbonInterface  $date  Tanggal acuan.
     * @return Builder<Garage>
     */
    public function scopeOccupiedOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereHas('rentals', fn (Builder $rental) => $rental->activeOn($date));
    }

    /**
     * Apakah garasi tersedia (tanpa rental aktif) pada tanggal tertentu.
     *
     * @param  CarbonInterface  $date  Tanggal acuan.
     * @return bool
     */
    public function isAvailableOn(CarbonInterface $date): bool
    {
        return ! $this->rentals()->activeOn($date)->exists();
    }
}
