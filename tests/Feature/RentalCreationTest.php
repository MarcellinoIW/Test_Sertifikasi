<?php

use App\Enums\GarageStatus;
use App\Enums\RentalType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 21, 9, 0, 0));
    Storage::fake('public');

    $this->tenant = User::factory()->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Data form sewa yang valid; bisa ditimpa per skenario.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function rentalPayload(Garage $garage, array $overrides = []): array
{
    return array_merge([
        'vehicle_type' => 'car',
        'plate_number' => 'B 1234 CD',
        'brand_model' => 'Toyota Avanza',
        'rental_type' => 'daily',
        'days' => 3,
        'garage_type' => $garage->type->value,
        'garage_id' => $garage->id,
        'payment_proof' => UploadedFile::fake()->image('bukti.jpg'),
    ], $overrides);
}

describe('Penyewa berhasil menyewa garasi', function () {
    it('sewa harian: status garasi menjadi used dan total = hari x harga harian', function () {
        $garage = Garage::factory()->car()->create(['daily_price' => 20000]);

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $rental = Rental::query()->sole();
        expect($rental->user_id)->toBe($this->tenant->id)
            ->and($rental->rental_type)->toBe(RentalType::Daily)
            ->and($rental->days)->toBe(3)
            ->and($rental->start_date->toDateString())->toBe('2026-11-21')
            ->and($rental->end_date->toDateString())->toBe('2026-11-24')
            ->and((float) $rental->daily_price)->toBe(20000.0)
            ->and((float) $rental->rent_cost)->toBe(60000.0)
            ->and((float) $rental->electricity_cost)->toBe(0.0)
            ->and((float) $rental->total_cost)->toBe(60000.0)
            ->and($garage->fresh()->status)->toBe(GarageStatus::Used);

        Storage::disk('public')->assertExists($rental->payment_proof);
    });

    it('sewa bulanan November: 30 hari dengan biaya 25 x harga harian', function () {
        $garage = Garage::factory()->car()->create(['daily_price' => 20000]);

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['rental_type' => 'monthly', 'days' => 5]))
            ->assertSessionHasNoErrors();

        $rental = Rental::query()->sole();
        expect($rental->days)->toBe(30)
            ->and($rental->end_date->toDateString())->toBe('2026-12-21')
            ->and((float) $rental->total_cost)->toBe(500000.0);
    });

    it('sewa bulanan tidak terkena batas hari harian walau form mengirim jumlah hari = 30', function () {
        $garage = Garage::factory()->car()->create(['daily_price' => 20000]);

        // Form mengisi field hari (read-only) dengan jumlah hari bulan berjalan.
        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['rental_type' => 'monthly', 'days' => 30]))
            ->assertSessionHasNoErrors();

        $rental = Rental::query()->sole();
        expect($rental->rental_type)->toBe(RentalType::Monthly)
            ->and($rental->days)->toBe(30)
            ->and((float) $rental->total_cost)->toBe(500000.0);
    });

    it('sewa bulanan tetap 30 hari meski field hari kosong atau diubah lewat request', function (mixed $days) {
        $garage = Garage::factory()->car()->create(['daily_price' => 20000]);

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['rental_type' => 'monthly', 'days' => $days]))
            ->assertSessionHasNoErrors();

        expect(Rental::query()->sole()->days)->toBe(30);
    })->with([null, 999, 'abc']);

    it('sewa harian wajib mengisi jumlah hari', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['days' => null]))
            ->assertSessionHasErrors(['days' => 'Jumlah hari wajib diisi untuk sewa harian.']);
    });

    it('mobil listrik di garasi E: biaya listrik (100 - 30) x Rp 2.500 = Rp 175.000 ikut dalam total', function () {
        $garage = Garage::factory()->electric()->create(['daily_price' => 30000, 'electricity_price_per_kwh' => 2500]);

        $this->actingAs($this->tenant)->post(route('tenant.rentals.store'), rentalPayload($garage, [
            'vehicle_type' => 'electric_car',
            'brand_model' => 'Hyundai Ioniq 5',
            'battery_remaining_kwh' => 30,
            'days' => 2,
        ]))->assertSessionHasNoErrors();

        $rental = Rental::query()->sole();
        expect((float) $rental->charged_kwh)->toBe(70.0)
            ->and((float) $rental->electricity_price_per_kwh)->toBe(2500.0)
            ->and((float) $rental->electricity_cost)->toBe(175000.0)
            ->and((float) $rental->total_cost)->toBe(235000.0)
            ->and((float) $rental->vehicle->battery_capacity_kwh)->toBe(100.0);
    });

    it('mobil listrik di garasi C tidak dikenai biaya listrik', function () {
        $garage = Garage::factory()->car()->create(['daily_price' => 20000]);

        $this->actingAs($this->tenant)->post(route('tenant.rentals.store'), rentalPayload($garage, [
            'vehicle_type' => 'electric_car',
            'battery_remaining_kwh' => 30,
            'days' => 1,
        ]))->assertSessionHasNoErrors();

        expect((float) Rental::query()->sole()->electricity_cost)->toBe(0.0)
            ->and((float) Rental::query()->sole()->total_cost)->toBe(20000.0);
    });

    it('sepeda boleh tanpa nomor plat', function () {
        $garage = Garage::factory()->bicycle()->create();

        $this->actingAs($this->tenant)->post(route('tenant.rentals.store'), rentalPayload($garage, [
            'vehicle_type' => 'bicycle',
            'plate_number' => null,
            'brand_model' => 'Polygon',
        ]))->assertSessionHasNoErrors();

        expect(Vehicle::query()->sole()->plate_number)->toBeNull();
    });

    it('mengabaikan angka biaya yang dikirim client dan menghitung ulang di server', function () {
        $garage = Garage::factory()->car()->create(['daily_price' => 20000]);

        $this->actingAs($this->tenant)->post(route('tenant.rentals.store'), rentalPayload($garage, [
            'total_cost' => 1,
            'rent_cost' => 1,
            'daily_price' => 1,
        ]))->assertSessionHasNoErrors();

        expect((float) Rental::query()->sole()->total_cost)->toBe(60000.0);
    });

    it('menyimpan nomor plat dalam format seragam huruf besar', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['plate_number' => 'b  1234cd']))
            ->assertSessionHasNoErrors();

        expect(Vehicle::query()->sole()->plate_number)->toBe('B 1234 CD');
    });
});

describe('Penyewaan ditolak', function () {
    it('ditolak jika garasi tidak kompatibel dengan kendaraan (mobil ke garasi sepeda)', function () {
        $garage = Garage::factory()->bicycle()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['vehicle_type' => 'car']))
            ->assertSessionHasErrors(['garage_type']);

        expect(Rental::query()->count())->toBe(0);
    });

    it('ditolak jika mobil bensin memilih garasi mobil listrik (E)', function () {
        $garage = Garage::factory()->electric()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage))
            ->assertSessionHasErrors(['garage_type']);
    });

    it('ditolak jika jumlah hari melebihi batas bulan berjalan (November maksimal 29)', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['days' => 30]))
            ->assertSessionHasErrors(['days' => 'Jumlah hari sewa harian maksimal 29 hari untuk bulan ini.']);
    });

    it('ditolak jika jumlah hari kurang dari 1', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['days' => 0]))
            ->assertSessionHasErrors(['days']);
    });

    it('ditolak jika garasi sudah dipakai rental aktif lain', function () {
        $garage = Garage::factory()->car()->create();
        Rental::factory()->daily(today()->subDay(), 5)->create(['garage_id' => $garage->id]);

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage))
            ->assertSessionHasErrors(['garage_id']);

        expect(Rental::query()->count())->toBe(1);
        Storage::disk('public')->assertDirectoryEmpty('payment-proofs');
    });

    it('ditolak jika tanpa bukti bayar', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['payment_proof' => null]))
            ->assertSessionHasErrors(['payment_proof' => 'Bukti pelunasan wajib diunggah.']);
    });

    it('ditolak jika bukti bayar bukan gambar JPG/PNG atau lebih dari 2 MB', function (UploadedFile $file) {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['payment_proof' => $file]))
            ->assertSessionHasErrors(['payment_proof']);
    })->with([
        'pdf' => fn () => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        'gif' => fn () => UploadedFile::fake()->image('bukti.gif'),
        'besar' => fn () => UploadedFile::fake()->image('bukti.jpg')->size(3000),
    ]);

    it('ditolak jika nomor plat sedang dipakai di rental aktif lain', function () {
        $vehicle = Vehicle::factory()->car()->create(['plate_number' => 'B 1234 CD']);
        Rental::factory()->daily(today()->subDay(), 5)->create(['user_id' => $vehicle->user_id, 'vehicle_id' => $vehicle->id]);
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['plate_number' => 'b 1234 cd']))
            ->assertSessionHasErrors(['plate_number']);
    });

    it('ditolak jika nomor plat kosong untuk kendaraan bermotor', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['plate_number' => '']))
            ->assertSessionHasErrors(['plate_number']);
    });

    it('ditolak jika sisa baterai mobil listrik kosong atau di luar 0-100', function (mixed $battery) {
        $garage = Garage::factory()->electric()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['vehicle_type' => 'electric_car', 'battery_remaining_kwh' => $battery]))
            ->assertSessionHasErrors(['battery_remaining_kwh']);
    })->with([null, -1, 101]);

    it('admin tidak bisa membuat sewa melalui route penyewa', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('tenant.rentals.store'), rentalPayload($garage))
            ->assertForbidden();
    });
});

describe('Ketersediaan garasi', function () {
    it('garasi kembali available pada tanggal selesai sewa', function () {
        $garage = Garage::factory()->car()->create();

        $this->actingAs($this->tenant)
            ->post(route('tenant.rentals.store'), rentalPayload($garage, ['days' => 3]))
            ->assertSessionHasNoErrors();
        expect($garage->fresh()->status)->toBe(GarageStatus::Used);

        // Sehari sebelum selesai (23 Nov) masih terpakai.
        Carbon::setTestNow(Carbon::create(2026, 11, 23, 9));
        $this->artisan('garages:sync-status')->assertSuccessful();
        expect($garage->fresh()->status)->toBe(GarageStatus::Used);

        // Tepat pada tanggal selesai (24 Nov) sudah available dan bisa disewa lagi.
        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9));
        $this->artisan('garages:sync-status')->assertSuccessful();
        expect($garage->fresh()->status)->toBe(GarageStatus::Available)
            ->and($garage->fresh()->isAvailableOn(today()))->toBeTrue();
    });

    it('endpoint JSON hanya mengembalikan garasi yang tersedia hari ini dan sesuai tipe', function () {
        Garage::factory()->electric()->create(['code' => 'E001']);
        $taken = Garage::factory()->electric()->create(['code' => 'E002']);
        Garage::factory()->car()->create(['code' => 'C001']);
        Rental::factory()->daily(today(), 2)->create(['garage_id' => $taken->id]);

        $this->actingAs($this->tenant)
            ->getJson(route('tenant.rentals.available-garages', ['vehicle_type' => 'electric_car', 'garage_type' => 'electric']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'E001')
            ->assertJsonPath('data.0.monthly_price', 750000)
            ->assertJsonPath('data.0.electricity_price_per_kwh', 2500);
    });

    it('endpoint JSON mengembalikan daftar kosong untuk kombinasi tidak kompatibel', function () {
        Garage::factory()->electric()->create();

        $this->actingAs($this->tenant)
            ->getJson(route('tenant.rentals.available-garages', ['vehicle_type' => 'car', 'garage_type' => 'electric']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('form sewa baru menampilkan batas hari dan tanggal mulai hari ini', function () {
        $this->actingAs($this->tenant)
            ->get(route('tenant.rentals.create'))
            ->assertOk()
            ->assertSee('1 – 29 hari')
            ->assertSee('21 Nov 2026');
    });
});
