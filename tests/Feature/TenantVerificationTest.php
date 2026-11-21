<?php

use App\Enums\UserStatus;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('menampilkan daftar pendaftar pending lengkap dengan NIK, nama, telepon, dan email', function () {
    $pending = User::factory()->pending()->create();
    $approved = User::factory()->approved()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertSee([$pending->nik, $pending->name, $pending->phone, $pending->email])
        ->assertDontSee($approved->email);
});

it('admin menerima pendaftar sehingga statusnya approved dan bisa login', function () {
    $tenant = User::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.tenants.approve', $tenant))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($tenant->fresh()->status)->toBe(UserStatus::Approved);

    auth()->logout();
    $this->post(route('login.store'), ['email' => $tenant->email, 'password' => 'password'])
        ->assertRedirect(route('tenant.dashboard'));
});

it('admin menolak pendaftar sehingga statusnya rejected', function () {
    $tenant = User::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.tenants.reject', $tenant))
        ->assertSessionHas('success');

    expect($tenant->fresh()->status)->toBe(UserStatus::Rejected);
});

it('tidak bisa memverifikasi ulang penyewa yang sudah diverifikasi', function () {
    $tenant = User::factory()->rejected()->create();

    $this->actingAs($this->admin)
        ->from(route('admin.tenants.index'))
        ->patch(route('admin.tenants.approve', $tenant))
        ->assertRedirect(route('admin.tenants.index'))
        ->assertSessionHas('error');

    expect($tenant->fresh()->status)->toBe(UserStatus::Rejected);
});

it('tidak bisa memverifikasi akun admin', function () {
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.tenants.reject', $otherAdmin))
        ->assertSessionHas('error');

    expect($otherAdmin->fresh()->status)->toBe(UserStatus::Approved);
});

it('melarang penyewa mengakses halaman verifikasi', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.tenants.index'))
        ->assertForbidden();
});
