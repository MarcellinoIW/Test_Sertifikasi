<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

// service untuk logika sewa garasi oleh penyewa
class RentalService
{
    // folder penyimpanan bukti bayar sewa
    public const PROOF_DIRECTORY = 'payment-proofs';

    // function constructor untuk memanggil kalkulator harga dan GarageService
    public function __construct(
        private readonly RentalPriceCalculator $calculator,
        private readonly GarageService $garageService,
    ) {}

    // function untuk mengambil tanggal mulai sewa paling awal
    public function earliestStartDate(?CarbonInterface $today = null): CarbonImmutable
    {
        return CarbonImmutable::instance($today ?? today())->startOfDay();
    }

    // function untuk mengambil tanggal mulai sewa paling akhir
    public function latestStartDate(?CarbonInterface $today = null): CarbonImmutable
    {
        return $this->earliestStartDate($today)->addDays((int) config('garasi.max_start_days_ahead'));
    }

    // function untuk menyiapkan data yang dipakai di form sewa
    public function formData(?CarbonInterface $today = null): array
    {
        $today = $this->earliestStartDate($today);
        $this->garageService->syncStatus($today);

        return [
            'today' => $today,
            'minStartDate' => $today,
            'maxStartDate' => $this->latestStartDate($today),
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

    // function untuk mencari garasi yang masih kosong
    public function availableGarages(VehicleType $vehicleType, GarageType $garageType, CarbonInterface $startDate, int $days): Collection
    {
        if (! $vehicleType->canUseGarage($garageType) || $days < 1) {
            return new Collection;
        }

        $this->garageService->syncStatus();

        $start = CarbonImmutable::instance($startDate)->startOfDay();

        return Garage::query()
            ->ofType($garageType)
            ->availableBetween($start, $start->addDays($days))
            ->orderBy('code')
            ->get();
    }

    // function untuk menyimpan sewa baru
    public function create(User $tenant, array $data, UploadedFile $paymentProof, ?CarbonInterface $today = null): Rental
    {
        $today = $this->earliestStartDate($today);
        $startDate = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $rentalType = RentalType::from($data['rental_type']);

        $vehicleType = VehicleType::from($data['vehicle_type']);
        $plate = $vehicleType->requiresPlate() ? $this->normalizePlate($data['plate_number'] ?? null) : null;
        $batteryRemaining = $vehicleType->isElectric() && isset($data['battery_remaining_kwh'])
            ? (float) $data['battery_remaining_kwh']
            : null;

        $this->ensureStartDateAllowed($startDate, $today);

        if ($vehicleType->requiresPlate() && $plate === null) {
            throw ValidationException::withMessages(['plate_number' => 'Nomor plat wajib diisi untuk kendaraan selain sepeda.']);
        }

        if ($vehicleType->isElectric() && $batteryRemaining === null) {
            throw ValidationException::withMessages(['battery_remaining_kwh' => 'Sisa baterai wajib diisi untuk mobil listrik.']);
        }

        $proofPath = $paymentProof->store(self::PROOF_DIRECTORY, 'public');

        try {
            return DB::transaction(function () use ($tenant, $data, $today, $startDate, $vehicleType, $rentalType, $plate, $batteryRemaining, $proofPath) {
                $garage = Garage::query()->lockForUpdate()->findOrFail($data['garage_id']);

                // Untuk bulanan, jumlah hari diabaikan dari client dan dihitung dari bulan tanggal mulai.
                $quote = $this->calculator->quote(
                    type: $rentalType,
                    days: $rentalType === RentalType::Daily && isset($data['days']) ? (int) $data['days'] : null,
                    startDate: $startDate,
                    dailyPrice: (float) $garage->daily_price,
                    vehicleType: $vehicleType,
                    garageType: $garage->type,
                    batteryRemainingKwh: $batteryRemaining,
                    pricePerKwh: $garage->electricity_price_per_kwh !== null ? (float) $garage->electricity_price_per_kwh : null,
                );

                $this->ensureGarageCanBeRented($garage, $vehicleType, $quote['start_date'], $quote['end_date']);

                if ($plate !== null) {
                    $this->ensurePlateNotBooked($plate, $quote['start_date'], $quote['end_date']);
                }

                $vehicle = $tenant->vehicles()->create([
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
                    ...$quote,
                    'start_date' => $quote['start_date']->toDateString(),
                    'end_date' => $quote['end_date']->toDateString(),
                ]);

                if ($quote['start_date']->isSameDay($today)) {
                    $garage->update(['status' => GarageStatus::Used]);
                }

                return $rental;
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($proofPath);

            throw $e;
        }
    }

    // function untuk mengambil data sewa milik penyewa
    public function tenantRentals(User $tenant, ?CarbonInterface $today = null): array
    {
        $today = $this->earliestStartDate($today);

        $rentals = $tenant->rentals()
            ->with(['garage', 'vehicle', 'extensions' => fn ($query) => $query->orderBy('created_at')])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        [$history, $current] = $rentals->partition(
            fn (Rental $rental) => $rental->statusOn($today) === 'finished',
        );

        return [
            'current' => $current->sortBy('start_date')->values(),
            'history' => $history->values(),
            'today' => $today,
        ];
    }

    // function untuk mengambil daftar sewa untuk admin
    public function paginateForAdmin(?string $status = null, ?string $search = null, ?CarbonInterface $today = null, int $perPage = 20): LengthAwarePaginator
    {
        $today = $this->earliestStartDate($today);
        $search = $search !== null ? trim($search) : null;

        return Rental::query()
            ->with(['garage', 'user', 'vehicle'])
            ->withCount('extensions')
            ->withSum('extensions', 'cost')
            ->when($status, fn ($query) => $query->withStatusOn($status, $today))
            ->when($search, function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->whereHas('garage', fn ($q) => $q->where('code', 'like', $like))
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like))
                        ->orWhereHas('vehicle', fn ($q) => $q->where('plate_number', 'like', $like));
                });
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    // function untuk merapikan format nomor plat
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

    // function untuk mengecek tanggal mulai sewa
    private function ensureStartDateAllowed(CarbonImmutable $startDate, CarbonImmutable $today): void
    {
        $latest = $this->latestStartDate($today);

        if ($startDate->lt($today) || $startDate->gt($latest)) {
            throw ValidationException::withMessages(['start_date' => 'Tanggal mulai harus antara '.$today->translatedFormat('d M Y').' dan '.$latest->translatedFormat('d M Y').'.']);
        }
    }

    // function untuk mengecek garasi bisa disewa atau tidak
    private function ensureGarageCanBeRented(Garage $garage, VehicleType $vehicleType, CarbonInterface $start, CarbonInterface $end): void
    {
        if (! $vehicleType->canUseGarage($garage->type)) {
            throw ValidationException::withMessages(['garage_id' => "Garasi {$garage->code} ({$garage->type->label()}) tidak dapat digunakan untuk {$vehicleType->label()}."]);
        }

        if (! $garage->isAvailableBetween($start, $end)) {
            throw ValidationException::withMessages(['garage_id' => "Garasi {$garage->code} sudah disewa/dibooking pada periode tersebut. Silakan pilih garasi atau tanggal lain."]);
        }
    }

    // function untuk mengecek nomor plat belum dipakai sewa lain
    private function ensurePlateNotBooked(string $plate, CarbonInterface $start, CarbonInterface $end): void
    {
        $inUse = Rental::query()
            ->overlapping($start, $end)
            ->whereHas('vehicle', fn ($query) => $query->where('plate_number', $plate))
            ->exists();

        if ($inUse) {
            throw ValidationException::withMessages(['plate_number' => "Kendaraan dengan plat {$plate} sudah tercatat di sewa lain pada periode tersebut."]);
        }
    }
}
