<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// model untuk data kendaraan penyewa
class Vehicle extends Model
{
    use HasFactory;

    // pola nomor plat Indonesia (contoh B 1234 CD)
    public const PLATE_PATTERN = '/^[A-Za-z]{1,2}\s*\d{1,4}(\s*[A-Za-z]{1,3})?$/';

    // kolom yang boleh diisi
    protected $fillable = [
        'user_id',
        'type',
        'plate_number',
        'brand_model',
        'battery_capacity_kwh',
    ];

    // function untuk mengatur tipe data kolom
    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'battery_capacity_kwh' => 'decimal:2',
        ];
    }

    // function relasi kendaraan ke pemiliknya
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // function relasi kendaraan ke data sewa
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    // function untuk menampilkan nama kendaraan secara singkat
    public function displayName(): string
    {
        $plate = $this->plate_number ? " ({$this->plate_number})" : '';

        return "{$this->type->label()} - {$this->brand_model}{$plate}";
    }
}
