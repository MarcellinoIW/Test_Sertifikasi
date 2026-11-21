<?php

namespace Database\Factories;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model Garage. Default: garasi mobil (C) Rp 20.000/hari.
 *
 * Kode garasi dibuat berurutan per tipe. Untuk kode spesifik (misal di
 * test generator kode) berikan atribut `code` secara eksplisit.
 *
 * @extends Factory<Garage>
 */
class GarageFactory extends Factory
{
    /**
     * Penghitung nomor urut terakhir per prefix selama proses berjalan.
     *
     * @var array<string, int>
     */
    protected static array $sequence = [];

    /**
     * Definisi atribut default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => GarageType::Car,
            'code' => fn (array $attributes) => static::nextCode($attributes['type']),
            'daily_price' => 20000,
            'electricity_price_per_kwh' => null,
            'status' => GarageStatus::Available,
        ];
    }

    /**
     * State: garasi sepeda (S).
     *
     * @return static
     */
    public function bicycle(): static
    {
        return $this->state(fn () => [
            'type' => GarageType::Bicycle,
            'daily_price' => 5000,
            'electricity_price_per_kwh' => null,
        ]);
    }

    /**
     * State: garasi mobil (C).
     *
     * @return static
     */
    public function car(): static
    {
        return $this->state(fn () => [
            'type' => GarageType::Car,
            'daily_price' => 20000,
            'electricity_price_per_kwh' => null,
        ]);
    }

    /**
     * State: garasi mobil listrik (E) dengan harga isi listrik per kWh.
     *
     * @return static
     */
    public function electric(): static
    {
        return $this->state(fn () => [
            'type' => GarageType::Electric,
            'daily_price' => 30000,
            'electricity_price_per_kwh' => 2500,
        ]);
    }

    /**
     * State: garasi berstatus terpakai.
     *
     * @return static
     */
    public function used(): static
    {
        return $this->state(fn () => ['status' => GarageStatus::Used]);
    }

    /**
     * Menghasilkan kode berikutnya untuk tipe garasi, melanjutkan nomor
     * terbesar yang ada di database maupun yang sudah dibuat factory.
     *
     * @param  GarageType|string  $type  Tipe garasi.
     * @return string Kode garasi, misal "C001".
     */
    protected static function nextCode(GarageType|string $type): string
    {
        $type = $type instanceof GarageType ? $type : GarageType::from($type);
        $prefix = $type->prefix();

        $lastCode = Garage::query()->where('code', 'like', $prefix.'%')->max('code');
        $lastInDb = $lastCode ? (int) substr((string) $lastCode, 1) : 0;

        $next = max($lastInDb, static::$sequence[$prefix] ?? 0) + 1;
        static::$sequence[$prefix] = $next;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
