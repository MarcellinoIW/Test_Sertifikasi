<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Exceptions\BusinessRuleException;
use App\Models\Garage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Logika bisnis pengelolaan garasi oleh admin.
 */
class GarageService
{
    /**
     * @param  GarageCodeGenerator  $codeGenerator  Pembuat kode garasi.
     * @param  GarageStatusService  $statusService  Sinkronisasi status garasi.
     */
    public function __construct(
        private readonly GarageCodeGenerator $codeGenerator,
        private readonly GarageStatusService $statusService,
    ) {}

    /**
     * Membuat sejumlah garasi sekaligus dalam satu transaksi.
     * Harga per kWh hanya disimpan untuk garasi tipe electric.
     *
     * @param  GarageType  $type  Tipe garasi.
     * @param  int  $qty  Jumlah garasi (1-999).
     * @param  float  $dailyPrice  Harga sewa harian.
     * @param  float|null  $electricityPricePerKwh  Harga isi listrik per kWh (wajib untuk electric).
     * @return Collection<int, Garage> Garasi yang baru dibuat.
     *
     * @throws BusinessRuleException Jika kuota kode terlampaui atau harga kWh tidak diisi untuk tipe electric.
     */
    public function createBatch(GarageType $type, int $qty, float $dailyPrice, ?float $electricityPricePerKwh = null): Collection
    {
        if ($type->hasCharging() && $electricityPricePerKwh === null) {
            throw new BusinessRuleException('Harga isi listrik per kWh wajib diisi untuk garasi mobil listrik.', 'electricity_price_per_kwh');
        }

        return DB::transaction(function () use ($type, $qty, $dailyPrice, $electricityPricePerKwh) {
            $codes = $this->codeGenerator->nextCodes($type, $qty, lock: true);
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

    /**
     * Daftar garasi terpaginasi dengan filter tipe/status. Status disinkronkan
     * lebih dulu dan rental aktif hari ini ikut dimuat untuk ditampilkan.
     *
     * @param  GarageType|null  $type  Filter tipe garasi.
     * @param  GarageStatus|null  $status  Filter status garasi.
     * @param  int  $perPage  Jumlah baris per halaman.
     * @return LengthAwarePaginator<int, Garage>
     */
    public function paginate(?GarageType $type = null, ?GarageStatus $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $today = today();
        $this->statusService->sync($today);

        return Garage::query()
            ->when($type, fn ($query) => $query->ofType($type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['rentals' => fn ($query) => $query->activeOn($today)->with(['user', 'vehicle'])])
            ->orderBy('type')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }
}
