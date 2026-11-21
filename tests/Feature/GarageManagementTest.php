<?php

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('Pembuatan garasi batch oleh admin', function () {
    it('membuat garasi sepeda pertama dengan kode S001 sampai S003', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.garages.store'), [
                'type' => GarageType::Bicycle->value,
                'quantity' => 3,
                'daily_price' => 5000,
            ])
            ->assertRedirect(route('admin.garages.index', ['type' => 'bicycle']))
            ->assertSessionHas('success');

        expect(Garage::query()->orderBy('code')->pluck('code')->all())->toBe(['S001', 'S002', 'S003']);

        $garage = Garage::query()->firstWhere('code', 'S001');
        expect($garage->type)->toBe(GarageType::Bicycle)
            ->and((float) $garage->daily_price)->toBe(5000.0)
            ->and($garage->electricity_price_per_kwh)->toBeNull()
            ->and($garage->status)->toBe(GarageStatus::Available);
    });

    it('melanjutkan kode garasi mobil setelah C009 menjadi C010 dan C011', function () {
        foreach (range(1, 9) as $n) {
            Garage::factory()->car()->create(['code' => sprintf('C%03d', $n)]);
        }

        $this->actingAs($this->admin)->post(route('admin.garages.store'), [
            'type' => GarageType::Car->value,
            'quantity' => 2,
            'daily_price' => 25000,
        ])->assertSessionHasNoErrors();

        expect(Garage::query()->ofType(GarageType::Car)->orderByDesc('code')->limit(2)->pluck('code')->all())
            ->toBe(['C011', 'C010'])
            ->and((float) Garage::query()->firstWhere('code', 'C010')->daily_price)->toBe(25000.0);
    });

    it('menyimpan harga per kWh untuk garasi mobil listrik', function () {
        $this->actingAs($this->admin)->post(route('admin.garages.store'), [
            'type' => GarageType::Electric->value,
            'quantity' => 1,
            'daily_price' => 30000,
            'electricity_price_per_kwh' => 2500,
        ])->assertSessionHasNoErrors();

        expect((float) Garage::query()->firstWhere('code', 'E001')->electricity_price_per_kwh)->toBe(2500.0);
    });

    it('mengabaikan harga per kWh untuk garasi selain mobil listrik', function () {
        $this->actingAs($this->admin)->post(route('admin.garages.store'), [
            'type' => GarageType::Car->value,
            'quantity' => 1,
            'daily_price' => 20000,
            'electricity_price_per_kwh' => 2500,
        ])->assertSessionHasNoErrors();

        expect(Garage::query()->firstWhere('code', 'C001')->electricity_price_per_kwh)->toBeNull();
    });

    it('menolak garasi mobil listrik tanpa harga per kWh', function () {
        $this->actingAs($this->admin)->post(route('admin.garages.store'), [
            'type' => GarageType::Electric->value,
            'quantity' => 1,
            'daily_price' => 30000,
        ])->assertSessionHasErrors(['electricity_price_per_kwh']);

        expect(Garage::query()->count())->toBe(0);
    });

    it('menolak jumlah di luar rentang 1 sampai 999', function (int $quantity) {
        $this->actingAs($this->admin)->post(route('admin.garages.store'), [
            'type' => GarageType::Car->value,
            'quantity' => $quantity,
            'daily_price' => 20000,
        ])->assertSessionHasErrors(['quantity']);
    })->with([0, 1000]);

    it('menolak batch yang membuat nomor kode melewati 999', function () {
        Garage::factory()->car()->create(['code' => 'C995']);

        $this->actingAs($this->admin)->post(route('admin.garages.store'), [
            'type' => GarageType::Car->value,
            'quantity' => 5,
            'daily_price' => 20000,
        ])->assertSessionHasErrors(['quantity']);

        expect(Garage::query()->count())->toBe(1);
    });

    it('melarang penyewa membuat garasi', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.garages.store'), ['type' => 'car', 'quantity' => 1, 'daily_price' => 1000])
            ->assertForbidden();

        expect(Garage::query()->count())->toBe(0);
    });
});

describe('Daftar garasi', function () {
    it('menampilkan garasi dan bisa difilter berdasarkan tipe', function () {
        Garage::factory()->bicycle()->create(['code' => 'S001']);
        Garage::factory()->car()->create(['code' => 'C001']);

        $this->actingAs($this->admin)
            ->get(route('admin.garages.index', ['type' => 'bicycle']))
            ->assertOk()
            ->assertSee('S001')
            ->assertDontSee('C001');
    });

    it('menyinkronkan status sehingga garasi dengan sewa aktif tampil sebagai terpakai', function () {
        $rental = Rental::factory()->create();
        expect($rental->garage->fresh()->status)->toBe(GarageStatus::Available);

        $this->actingAs($this->admin)
            ->get(route('admin.garages.index', ['status' => 'used']))
            ->assertOk()
            ->assertSee($rental->garage->code)
            ->assertSee($rental->user->name);

        expect($rental->garage->fresh()->status)->toBe(GarageStatus::Used);
    });

    it('menolak filter dengan nilai tidak valid', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.garages.index', ['type' => 'pesawat']))
            ->assertSessionHasErrors(['type']);
    });
});
