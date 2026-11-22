<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// service untuk logika garasi (kode garasi, daftar garasi, status garasi)
class GarageService
{
    // jumlah digit nomor kode garasi
    private const DIGITS = 3;

    // function untuk membuat banyak garasi sekaligus
    public function createBatch(GarageType $type, int $qty, float $dailyPrice, ?float $electricityPricePerKwh = null): Collection
    {
        if ($type->hasCharging() && $electricityPricePerKwh === null) {
            throw ValidationException::withMessages(['electricity_price_per_kwh' => 'Harga isi listrik per kWh wajib diisi untuk garasi mobil listrik.']);
        }

        return DB::transaction(function () use ($type, $qty, $dailyPrice, $electricityPricePerKwh) {
            $codes = $this->nextCodes($type, $qty, lock: true);
            $now = now();

            $rows = array_map(fn (string $code) => [
                'code' => $code,
                'type' => $type->value,
                'daily_price' => $dailyPrice,
                'electricity_price_per_kwh' => $type->hasCharging() ? $electricityPricePerKwh : null,
                'status' => GarageStatus::Available->value,
                'created_at' => $now,
                'updated_at' => $now,
            ], $codes);

            Garage::query()->insert($rows);

            return Garage::query()->whereIn('code', $codes)->orderBy('code')->get();
        });
    }

    // function untuk mengambil daftar garasi dengan filter
    public function paginate(?GarageType $type = null, ?GarageStatus $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $today = today();
        $this->syncStatus($today);

        return Garage::query()
            ->when($type, fn ($query) => $query->ofType($type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['rentals' => fn ($query) => $query->activeOn($today)->with(['user', 'vehicle'])])
            ->orderBy('type')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    // function untuk menyesuaikan status garasi (tersedia / terpakai)
    public function syncStatus(?CarbonInterface $date = null): array
    {
        $date ??= today();

        $freed = Garage::query()
            ->where('status', GarageStatus::Used)
            ->availableOn($date)
            ->update(['status' => GarageStatus::Available]);

        $occupied = Garage::query()
            ->where('status', GarageStatus::Available)
            ->occupiedOn($date)
            ->update(['status' => GarageStatus::Used]);

        return ['available' => $freed, 'used' => $occupied];
    }

    // function untuk membuat kode garasi berikutnya
    public function nextCodes(GarageType $type, int $qty, bool $lock = false): array
    {
        $last = $this->lastNumber($type, $lock);
        $max = $this->maxNumber();

        if ($qty < 1 || $last + $qty > $max) {
            throw ValidationException::withMessages([
                'quantity' => "Jumlah melebihi batas. Garasi {$type->label()} terakhir bernomor {$this->format($type, $last)}, "
                    ."sisa kuota hanya {$this->remaining($type)} garasi (maksimal {$this->format($type, $max)}).",
            ]);
        }

        return array_map(
            fn (int $number) => $this->format($type, $number),
            range($last + 1, $last + $qty),
        );
    }

    // function untuk mengambil nomor garasi terakhir
    public function lastNumber(GarageType $type, bool $lock = false): int
    {
        $query = Garage::query()->where('code', 'like', $type->prefix().'%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastCode = $query->max('code');

        return $lastCode ? (int) substr((string) $lastCode, strlen($type->prefix())) : 0;
    }

    // function untuk menghitung sisa nomor garasi yang masih bisa dibuat
    public function remaining(GarageType $type): int
    {
        return max(0, $this->maxNumber() - $this->lastNumber($type));
    }

    // function untuk mengambil nomor terakhir dari semua tipe garasi
    public function lastNumbers(): array
    {
        $result = [];
        foreach (GarageType::cases() as $type) {
            $result[$type->value] = $this->lastNumber($type);
        }

        return $result;
    }

    // function untuk mengubah nomor menjadi kode garasi (contoh C010)
    public function format(GarageType $type, int $number): string
    {
        return $type->prefix().str_pad((string) $number, self::DIGITS, '0', STR_PAD_LEFT);
    }

    // function untuk mengambil batas maksimal nomor garasi
    private function maxNumber(): int
    {
        return (int) config('garasi.max_garages_per_batch');
    }
}
