<?php

namespace Database\Seeders;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VerificationAction;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\GarageService;
use App\Services\RentalPriceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

// seeder untuk membuat data demo (garasi, penyewa, sewa)
class DemoSeeder extends Seeder
{
    // lokasi gambar bukti bayar demo
    private const DEMO_PROOF = 'payment-proofs/demo.png';

    // function constructor untuk memanggil kalkulator harga
    public function __construct(private readonly RentalPriceCalculator $calculator) {}

    // function untuk membuat semua data demo
    public function run(GarageService $garageService): void
    {
        $this->storeDemoProof();

        $today = today();
        $admin = User::query()->where('email', 'admin@garasi.test')->firstOrFail();

        $garages = $this->seedGarages();
        [$tenantA, $tenantB] = $this->seedTenants($admin);

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

        $garageService->syncStatus($today);
    }

    // function untuk membuat data garasi demo
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

    // function untuk membuat data penyewa demo
    private function seedTenants(User $admin): array
    {
        $registeredAt = today()->startOfYear()->setTime(9, 0);

        $tenantA = User::factory()->approved()->create([
            'nik' => '3201010101900001',
            'name' => 'Budi Santoso',
            'phone' => '081234567801',
            'email' => 'budi@garasi.test',
            'created_at' => $registeredAt,
        ]);

        $tenantB = User::factory()->approved()->create([
            'nik' => '3201010101900002',
            'name' => 'Siti Rahmawati',
            'phone' => '081234567802',
            'email' => 'siti@garasi.test',
            'created_at' => $registeredAt,
        ]);

        $pending = User::factory()->pending()->create([
            'nik' => '3201010101900003',
            'name' => 'Andi Pratama',
            'phone' => '081234567803',
            'email' => 'andi@garasi.test',
            'created_at' => today()->subDay()->setTime(14, 0),
        ]);

        foreach ([$tenantA, $tenantB] as $tenant) {
            $tenant->verifications()->create(['action' => VerificationAction::Registered, 'created_at' => $registeredAt]);
            $tenant->verifications()->create([
                'action' => VerificationAction::Approved,
                'admin_id' => $admin->id,
                'created_at' => $registeredAt->copy()->addHours(2),
            ]);
        }
        $pending->verifications()->create(['action' => VerificationAction::Registered, 'created_at' => $pending->created_at]);

        return [$tenantA, $tenantB];
    }

    // function untuk membuat data sewa demo
    private function rent(User $tenant, Garage $garage, string $vehicle, CarbonInterface $start, ?int $days, ?float $batteryRemaining = null): Rental
    {
        $factoryState = $vehicle === 'electric' ? 'electricCar' : $vehicle;
        $vehicleModel = Vehicle::factory()->{$factoryState}()->create(['user_id' => $tenant->id]);

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
            ...$quote,
            'start_date' => $quote['start_date']->toDateString(),
            'end_date' => $quote['end_date']->toDateString(),
        ]);
    }

    // function untuk membuat data perpanjangan demo
    private function extend(Rental $rental, User $admin, int $days): void
    {
        $breakdown = $this->calculator->extensionCost($rental->end_date, $days, (float) $rental->daily_price);
        $createdAt = $rental->end_date->copy()->subDay()->setTime(10, 0);

        RentalExtension::factory()->create([
            'rental_id' => $rental->id,
            'admin_id' => $admin->id,
            'old_end_date' => $breakdown['old_end_date']->toDateString(),
            'new_end_date' => $breakdown['new_end_date']->toDateString(),
            'additional_days' => $breakdown['additional_days'],
            'monthly_blocks' => $breakdown['monthly_blocks'],
            'daily_days' => $breakdown['daily_days'],
            'cost' => $breakdown['cost'],
            'payment_proof' => self::DEMO_PROOF,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $rental->update(['end_date' => $breakdown['new_end_date']->toDateString()]);
    }

    // function untuk menyimpan gambar bukti bayar demo
    private function storeDemoProof(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        Storage::disk('public')->put(self::DEMO_PROOF, $png);
    }
}
