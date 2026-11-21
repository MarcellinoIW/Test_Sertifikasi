<?php

namespace Database\Seeders;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GarageStatusService;
use App\Services\RentalPriceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Data demo: 25 garasi, 3 penyewa (2 approved, 1 pending), dan beberapa
 * rental tersebar di bulan-bulan tahun berjalan supaya laporan berisi.
 */
class DemoSeeder extends Seeder
{
    /**
     * Path gambar bukti bayar contoh pada disk public.
     */
    private const DEMO_PROOF = 'payment-proofs/demo.png';

    /**
     * @param  RentalPriceCalculator  $calculator  Kalkulator harga sewa.
     */
    public function __construct(private readonly RentalPriceCalculator $calculator) {}

    /**
     * Jalankan seeder.
     *
     * @param  GarageStatusService  $statusService  Service sinkronisasi status garasi.
     */
    public function run(GarageStatusService $statusService): void
    {
        $this->storeDemoProof();

        $garages = $this->seedGarages();
        [$tenantA, $tenantB] = $this->seedTenants();

        $today = today();
        $admin = User::query()->where('email', 'admin@garasi.test')->firstOrFail();

        // Rental aktif hari ini (satu per tipe garasi).
        $this->rent($tenantA, $garages[GarageType::Bicycle->value][0], 'motorcycle', $today->copy()->subDays(6), 10);
        $this->rent($tenantB, $garages[GarageType::Car->value][0], 'car', $today->copy()->startOfMonth(), null);
        $this->rent($tenantA, $garages[GarageType::Electric->value][0], 'electric', $today->copy()->subDays(3), 7, 30);

        // Rental yang sudah selesai pada bulan-bulan sebelumnya di tahun berjalan.
        $types = [GarageType::Bicycle->value, GarageType::Car->value, GarageType::Electric->value];
        $vehicleFor = [GarageType::Bicycle->value => 'bicycle', GarageType::Car->value => 'car', GarageType::Electric->value => 'electric'];

        for ($k = 1; $k <= 10; $k++) {
            $start = $today->copy()->startOfMonth()->subMonthsNoOverflow($k)->addDays(4);
            if ($start->year !== $today->year) {
                break;
            }

            $type = $types[$k % 3];
            $garage = $garages[$type][intdiv($k, 3) + 1];
            $tenant = $k % 2 === 0 ? $tenantA : $tenantB;
            $days = $k % 4 === 0 ? null : 2 + $k;

            $rental = $this->rent($tenant, $garage, $vehicleFor[$type], $start, $days, $type === GarageType::Electric->value ? 20 + $k : null);

            // Satu contoh perpanjangan 5 hari harian oleh admin.
            if ($k === 2) {
                $this->extend($rental, $admin, 5);
            }
        }

        $statusService->sync($today);
    }

    /**
     * Membuat 10 garasi S, 10 garasi C, dan 5 garasi E.
     *
     * @return array<string, Collection<int, Garage>>
     */
    private function seedGarages(): array
    {
        $make = fn (string $state, string $prefix, int $count) => Garage::factory()
            ->{$state}()
            ->count($count)
            ->state(new Sequence(fn (Sequence $s) => ['code' => $prefix.str_pad((string) ($s->index + 1), 3, '0', STR_PAD_LEFT)]))
            ->create()
            ->values();

        return [
            GarageType::Bicycle->value => $make('bicycle', 'S', 10),
            GarageType::Car->value => $make('car', 'C', 10),
            GarageType::Electric->value => $make('electric', 'E', 5),
        ];
    }

    /**
     * Membuat 2 penyewa approved dan 1 penyewa pending.
     *
     * @return array{0: User, 1: User}
     */
    private function seedTenants(): array
    {
        $tenantA = User::factory()->approved()->create([
            'nik' => '3201010101900001',
            'name' => 'Budi Santoso',
            'phone' => '081234567801',
            'email' => 'budi@garasi.test',
        ]);

        $tenantB = User::factory()->approved()->create([
            'nik' => '3201010101900002',
            'name' => 'Siti Rahmawati',
            'phone' => '081234567802',
            'email' => 'siti@garasi.test',
        ]);

        User::factory()->pending()->create([
            'nik' => '3201010101900003',
            'name' => 'Andi Pratama',
            'phone' => '081234567803',
            'email' => 'andi@garasi.test',
            'created_at' => today()->subDay(),
        ]);

        return [$tenantA, $tenantB];
    }

    /**
     * Membuat kendaraan dan rental demo dengan biaya yang konsisten.
     *
     * @param  User  $tenant  Penyewa.
     * @param  Garage  $garage  Garasi yang disewa.
     * @param  string  $vehicle  State factory kendaraan: bicycle|motorcycle|car|electric.
     * @param  CarbonInterface  $start  Tanggal mulai.
     * @param  int|null  $days  Jumlah hari (sewa harian) atau null untuk sewa bulanan.
     * @param  float|null  $batteryRemaining  Sisa baterai kWh (khusus mobil listrik).
     * @return Rental
     */
    private function rent(User $tenant, Garage $garage, string $vehicle, CarbonInterface $start, ?int $days, ?float $batteryRemaining = null): Rental
    {
        $vehicleState = $vehicle === 'electric' ? 'electricCar' : $vehicle;
        $vehicleModel = Vehicle::factory()->{$vehicleState}()->create(['user_id' => $tenant->id]);

        // Biaya dihitung oleh kalkulator yang sama dengan aplikasi (tanpa logika duplikat).
        $quote = $this->calculator->quote(
            type: $days === null ? RentalType::Monthly : RentalType::Daily,
            days: $days,
            startDate: $start,
            dailyPrice: (float) $garage->daily_price,
            vehicleType: $vehicleModel->type,
            garageType: $garage->type,
            batteryRemainingKwh: $batteryRemaining,
            pricePerKwh: $garage->electricity_price_per_kwh !== null ? (float) $garage->electricity_price_per_kwh : null,
        );

        return Rental::factory()->create([
            'user_id' => $tenant->id,
            'garage_id' => $garage->id,
            'vehicle_id' => $vehicleModel->id,
            'payment_proof' => self::DEMO_PROOF,
            'created_at' => $start,
            'updated_at' => $start,
            ...$quote->toRentalAttributes(),
        ]);
    }

    /**
     * Membuat perpanjangan demo (dihitung oleh kalkulator) dan memperbarui tanggal selesai rental.
     *
     * @param  Rental  $rental  Rental yang diperpanjang.
     * @param  User  $admin  Admin yang memproses.
     * @param  int  $days  Tambahan hari.
     */
    private function extend(Rental $rental, User $admin, int $days): void
    {
        $breakdown = $this->calculator->extensionCost($rental->end_date, $days, (float) $rental->daily_price);
        $createdAt = $rental->end_date->copy()->subDay()->setTime(10, 0);

        RentalExtension::factory()->create([
            'rental_id' => $rental->id,
            'admin_id' => $admin->id,
            'old_end_date' => $breakdown->oldEndDate->toDateString(),
            'new_end_date' => $breakdown->newEndDate->toDateString(),
            'additional_days' => $breakdown->additionalDays,
            'monthly_blocks' => $breakdown->monthlyBlocks,
            'daily_days' => $breakdown->dailyDays,
            'cost' => $breakdown->cost,
            'payment_proof' => self::DEMO_PROOF,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $rental->update(['end_date' => $breakdown->newEndDate->toDateString()]);
    }

    /**
     * Menyimpan gambar PNG kecil sebagai bukti bayar contoh.
     */
    private function storeDemoProof(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        Storage::disk('public')->put(self::DEMO_PROOF, $png);
    }
}
