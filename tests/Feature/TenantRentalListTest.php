<?php

use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 21, 9));
    $this->tenant = User::factory()->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('halaman Sewa Saya memisahkan sewa aktif dan riwayat milik penyewa', function () {
    $active = Rental::factory()->daily(today()->subDay(), 5)->create(['user_id' => $this->tenant->id]);
    $finished = Rental::factory()->daily(today()->subDays(10), 3)->create(['user_id' => $this->tenant->id]);

    $response = $this->actingAs($this->tenant)->get(route('tenant.rentals.index'))->assertOk();

    expect($response->viewData('active')->pluck('id')->all())->toBe([$active->id])
        ->and($response->viewData('history')->pluck('id')->all())->toBe([$finished->id]);
});

it('halaman Sewa Saya tidak menampilkan sewa milik penyewa lain', function () {
    $other = Rental::factory()->create();

    $this->actingAs($this->tenant)
        ->get(route('tenant.rentals.index'))
        ->assertOk()
        ->assertDontSee($other->garage->code);
});

it('menampilkan riwayat perpanjangan pada sewa', function () {
    $rental = Rental::factory()->daily(today()->subDay(), 5)->create(['user_id' => $this->tenant->id]);
    RentalExtension::factory()->create([
        'rental_id' => $rental->id,
        'old_end_date' => '2026-11-25',
        'new_end_date' => '2026-11-30',
        'additional_days' => 5,
        'cost' => 100000,
    ]);

    $this->actingAs($this->tenant)
        ->get(route('tenant.rentals.index'))
        ->assertOk()
        ->assertSee('+5 hari')
        ->assertSee('Rp 100.000');
});

it('penyewa bisa melihat detail sewanya sendiri', function () {
    $rental = Rental::factory()->create(['user_id' => $this->tenant->id]);

    $this->actingAs($this->tenant)
        ->get(route('tenant.rentals.show', $rental))
        ->assertOk()
        ->assertSee($rental->garage->code);
});

it('penyewa tidak bisa melihat detail sewa milik penyewa lain', function () {
    $other = Rental::factory()->create();

    $this->actingAs($this->tenant)
        ->get(route('tenant.rentals.show', $other))
        ->assertForbidden();
});
