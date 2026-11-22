<?php

namespace App\Models;

use App\Enums\RentalType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

// model untuk data sewa garasi
class Rental extends Model
{
    use HasFactory;

    // daftar status sewa beserta labelnya
    public const STATUSES = [
        'upcoming' => 'Akan Datang',
        'active' => 'Aktif',
        'finished' => 'Selesai',
    ];

    // kolom yang boleh diisi
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

    // function untuk mengatur tipe data kolom
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

    // function relasi sewa ke penyewa
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // function relasi sewa ke garasi
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    // function relasi sewa ke kendaraan
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // function relasi sewa ke data perpanjangan
    public function extensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class);
    }

    // function untuk filter sewa yang aktif pada tanggal tertentu
    public function scopeActiveOn(Builder $query, CarbonInterface $date): Builder
    {
        $day = $date->toDateString();

        return $query->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>', $day);
    }

    // function untuk filter sewa yang periodenya bentrok
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->whereDate('start_date', '<', $end->toDateString())
            ->whereDate('end_date', '>', $start->toDateString());
    }

    // function untuk filter sewa yang belum selesai
    public function scopeNotFinishedOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('end_date', '>', $date->toDateString());
    }

    // function untuk filter sewa berdasarkan status
    public function scopeWithStatusOn(Builder $query, string $status, CarbonInterface $date): Builder
    {
        $day = $date->toDateString();

        return match ($status) {
            'upcoming' => $query->whereDate('start_date', '>', $day),
            'active' => $query->activeOn($date),
            'finished' => $query->whereDate('end_date', '<=', $day),
        };
    }

    // function untuk mengecek sewa aktif pada tanggal tertentu
    public function isActiveOn(CarbonInterface $date): bool
    {
        return $this->statusOn($date) === 'active';
    }

    // function untuk menentukan status sewa (akan datang / aktif / selesai)
    public function statusOn(CarbonInterface $date): string
    {
        $day = $date->copy()->startOfDay();

        return match (true) {
            $this->start_date->gt($day) => 'upcoming',
            $this->end_date->gt($day) => 'active',
            default => 'finished',
        };
    }

    // function untuk menghitung total bayar termasuk perpanjangan
    public function grandTotal(): float
    {
        $extensions = $this->relationLoaded('extensions')
            ? $this->extensions->sum('cost')
            : $this->extensions()->sum('cost');

        return (float) $this->total_cost + (float) $extensions;
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
