<?php

namespace App\Models;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// model untuk data garasi
class Garage extends Model
{
    use HasFactory;

    // kolom yang boleh diisi
    protected $fillable = [
        'code',
        'type',
        'daily_price',
        'electricity_price_per_kwh',
        'status',
    ];

    // function untuk mengatur tipe data kolom
    protected function casts(): array
    {
        return [
            'type' => GarageType::class,
            'status' => GarageStatus::class,
            'daily_price' => 'decimal:2',
            'electricity_price_per_kwh' => 'decimal:2',
        ];
    }

    // function relasi garasi ke data sewa
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    // function untuk filter garasi berdasarkan tipe
    public function scopeOfType(Builder $query, GarageType|string $type): Builder
    {
        return $query->where('type', $type instanceof GarageType ? $type->value : $type);
    }

    // function untuk filter garasi yang kosong pada tanggal tertentu
    public function scopeAvailableOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDoesntHave('rentals', fn (Builder $rental) => $rental->activeOn($date));
    }

    // function untuk filter garasi yang kosong pada periode tertentu
    public function scopeAvailableBetween(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->whereDoesntHave('rentals', fn (Builder $rental) => $rental->overlapping($start, $end));
    }

    // function untuk mengecek garasi kosong pada periode tertentu
    public function isAvailableBetween(CarbonInterface $start, CarbonInterface $end): bool
    {
        return ! $this->rentals()->overlapping($start, $end)->exists();
    }

    // function untuk filter garasi yang sedang dipakai pada tanggal tertentu
    public function scopeOccupiedOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereHas('rentals', fn (Builder $rental) => $rental->activeOn($date));
    }

    // function untuk mengecek garasi kosong pada tanggal tertentu
    public function isAvailableOn(CarbonInterface $date): bool
    {
        return ! $this->rentals()->activeOn($date)->exists();
    }
}
