<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Exceptions\BusinessRuleException;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Logika bisnis sewa garasi baru oleh penyewa.
 */
class RentalService
{
    /**
     * Folder penyimpanan bukti pelunasan pada disk public.
     */
    public const PROOF_DIRECTORY = 'payment-proofs';

    /**
     * @param  RentalPriceCalculator  $calculator  Kalkulator harga (sumber kebenaran biaya).
     * @param  GarageStatusService  $statusService  Sinkronisasi status garasi.
     */
    public function __construct(
        private readonly RentalPriceCalculator $calculator,
        private readonly GarageStatusService $statusService,
    ) {}

    /**
     * Data pendukung form sewa baru (untuk tampilan & pratinjau JS).
     * Status garasi disinkronkan lebih dulu.
     *
     * @param  CarbonInterface|null  $today  Tanggal acuan; default hari ini.
     * @return array<string, mixed>
     */
    public function formData(?CarbonInterface $today = null): array
    {
        $today ??= today();
        $this->statusService->sync($today);

        return [
            'today' => $today->copy(),
            'vehicleTypes' => VehicleType::cases(),
            'garageTypes' => GarageType::cases(),
            'rentalTypes' => RentalType::cases(),
            'maxDailyDays' => $this->calculator->maxDailyDays($today),
            'monthlyDays' => $this->calculator->monthlyDays($today),
            'monthlyMultiplier' => $this->calculator->monthlyMultiplier(),
            'batteryCapacity' => $this->calculator->batteryCapacityKwh(),
            'compatibility' => collect(VehicleType::cases())->mapWithKeys(fn (VehicleType $type) => [
                $type->value => [
                    'garageTypes' => array_map(fn (GarageType $g) => $g->value, $type->allowedGarageTypes()),
                    'requiresPlate' => $type->requiresPlate(),
                    'isElectric' => $type->isElectric(),
                ],
            ])->all(),
        ];
    }

    /**
     * Garasi bertipe tertentu yang tersedia pada tanggal acuan dan
     * kompatibel dengan tipe kendaraan.
     *
     * @param  VehicleType  $vehicleType  Tipe kendaraan.
     * @param  GarageType  $garageType  Tipe garasi yang dipilih.
     * @param  CarbonInterface|null  $date  Tanggal acuan; default hari ini.
     * @return Collection<int, Garage>
     */
    public function availableGarages(VehicleType $vehicleType, GarageType $garageType, ?CarbonInterface $date = null): Collection
    {
        if (! $vehicleType->canUseGarage($garageType)) {
            return new Collection;
        }

        $date ??= today();
        $this->statusService->sync($date);

        return Garage::query()
            ->ofType($garageType)
            ->availableOn($date)
            ->orderBy('code')
            ->get();
    }

    /**
     * Membuat sewa baru: simpan bukti bayar, lalu dalam satu transaksi buat
     * kendaraan + rental dan ubah status garasi menjadi used.
     *
     * Garasi dikunci (SELECT ... FOR UPDATE) lalu dicek ulang ketersediaannya
     * untuk mencegah dua penyewa mengambil garasi yang sama bersamaan.
     *
     * @param  User  $tenant  Penyewa.
     * @param  array{vehicle_type: string, plate_number?: string|null, brand_model: string, battery_remaining_kwh?: float|string|null, rental_type: string, days?: int|string|null, garage_id: int|string}  $data  Data form tervalidasi.
     * @param  UploadedFile  $paymentProof  Gambar bukti pelunasan.
     * @param  CarbonInterface|null  $today  Tanggal mulai; default hari ini.
     * @return Rental
     *
     * @throws BusinessRuleException Jika garasi tidak kompatibel/terpakai, plat sedang dipakai, atau hari di luar batas.
     */
    public function create(User $tenant, array $data, UploadedFile $paymentProof, ?CarbonInterface $today = null): Rental
    {
        $today ??= today();
        $vehicleType = VehicleType::from($data['vehicle_type']);
        $rentalType = RentalType::from($data['rental_type']);
        $plate = $vehicleType->requiresPlate() ? $this->normalizePlate($data['plate_number'] ?? null) : null;
        $batteryRemaining = $vehicleType->isElectric() && isset($data['battery_remaining_kwh'])
            ? (float) $data['battery_remaining_kwh']
            : null;

        if ($vehicleType->requiresPlate() && $plate === null) {
            throw new BusinessRuleException('Nomor plat wajib diisi untuk kendaraan selain sepeda.', 'plate_number');
        }

        if ($vehicleType->isElectric() && $batteryRemaining === null) {
            throw new BusinessRuleException('Sisa baterai wajib diisi untuk mobil listrik.', 'battery_remaining_kwh');
        }

        $proofPath = $paymentProof->store(self::PROOF_DIRECTORY, 'public');

        try {
            return DB::transaction(function () use ($tenant, $data, $today, $vehicleType, $rentalType, $plate, $batteryRemaining, $proofPath) {
                /** @var Garage $garage */
                $garage = Garage::query()->lockForUpdate()->findOrFail($data['garage_id']);

                $this->ensureGarageCanBeRented($garage, $vehicleType, $today);

                if ($plate !== null) {
                    $this->ensurePlateNotInActiveRental($plate, $today);
                }

                $quote = $this->calculator->quote(
                    type: $rentalType,
                    days: isset($data['days']) ? (int) $data['days'] : null,
                    startDate: $today,
                    dailyPrice: (float) $garage->daily_price,
                    vehicleType: $vehicleType,
                    garageType: $garage->type,
                    batteryRemainingKwh: $batteryRemaining,
                    pricePerKwh: $garage->electricity_price_per_kwh !== null ? (float) $garage->electricity_price_per_kwh : null,
                );

                $vehicle = Vehicle::query()->create([
                    'user_id' => $tenant->id,
                    'type' => $vehicleType,
                    'plate_number' => $plate,
                    'brand_model' => trim($data['brand_model']),
                    'battery_capacity_kwh' => $vehicleType->isElectric() ? $this->calculator->batteryCapacityKwh() : null,
                ]);

                $rental = Rental::query()->create([
                    'user_id' => $tenant->id,
                    'garage_id' => $garage->id,
                    'vehicle_id' => $vehicle->id,
                    'payment_proof' => $proofPath,
                    ...$quote->toRentalAttributes(),
                ]);

                $garage->update(['status' => GarageStatus::Used]);

                return $rental;
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($proofPath);

            throw $e;
        }
    }

    /**
     * Sewa milik penyewa, dipisah menjadi aktif dan riwayat (sudah selesai).
     *
     * @param  User  $tenant  Penyewa.
     * @param  CarbonInterface|null  $today  Tanggal acuan; default hari ini.
     * @return array{active: Collection<int, Rental>, history: Collection<int, Rental>}
     */
    public function tenantRentals(User $tenant, ?CarbonInterface $today = null): array
    {
        $today ??= today();

        $rentals = $tenant->rentals()
            ->with(['garage', 'vehicle', 'extensions' => fn ($query) => $query->orderBy('created_at')])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return [
            'active' => $rentals->filter(fn (Rental $rental) => $rental->isActiveOn($today))->values(),
            'history' => $rentals->reject(fn (Rental $rental) => $rental->isActiveOn($today))->values(),
        ];
    }

    /**
     * Seragamkan format nomor plat: huruf besar, spasi tunggal ("b  1234cd" -> "B 1234 CD").
     *
     * @param  string|null  $plate  Nomor plat mentah.
     * @return string|null Null jika kosong.
     */
    public function normalizePlate(?string $plate): ?string
    {
        $plate = Str::upper(trim((string) $plate));
        if ($plate === '') {
            return null;
        }

        $plate = preg_replace('/\s+/', '', $plate);

        return preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/', $plate, $m)
            ? trim("{$m[1]} {$m[2]} {$m[3]}")
            : $plate;
    }

    /**
     * Pastikan garasi kompatibel dengan kendaraan dan tidak punya rental aktif.
     *
     * @param  Garage  $garage  Garasi (sudah dikunci).
     * @param  VehicleType  $vehicleType  Tipe kendaraan.
     * @param  CarbonInterface  $today  Tanggal mulai.
     *
     * @throws BusinessRuleException
     */
    private function ensureGarageCanBeRented(Garage $garage, VehicleType $vehicleType, CarbonInterface $today): void
    {
        if (! $vehicleType->canUseGarage($garage->type)) {
            throw new BusinessRuleException(
                "Garasi {$garage->code} ({$garage->type->label()}) tidak dapat digunakan untuk {$vehicleType->label()}.",
                'garage_id',
            );
        }

        if (! $garage->isAvailableOn($today)) {
            throw new BusinessRuleException(
                "Garasi {$garage->code} sudah disewa orang lain. Silakan pilih garasi lain.",
                'garage_id',
            );
        }
    }

    /**
     * Pastikan nomor plat tidak sedang tercatat di rental aktif lain.
     *
     * @param  string  $plate  Nomor plat ternormalisasi.
     * @param  CarbonInterface  $today  Tanggal acuan.
     *
     * @throws BusinessRuleException
     */
    private function ensurePlateNotInActiveRental(string $plate, CarbonInterface $today): void
    {
        $inUse = Rental::query()
            ->activeOn($today)
            ->whereHas('vehicle', fn ($query) => $query->where('plate_number', $plate))
            ->exists();

        if ($inUse) {
            throw new BusinessRuleException("Kendaraan dengan plat {$plate} sedang menyewa garasi lain.", 'plate_number');
        }
    }
}
