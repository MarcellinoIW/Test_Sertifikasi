<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

describe('Registrasi penyewa', function () {
    it('menyimpan pendaftar baru dengan role penyewa dan status pending', function () {
        $response = $this->post(route('register.store'), [
            'nik' => '3201234567890001',
            'name' => 'Penyewa Baru',
            'phone' => '081234567890',
            'email' => 'baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $user = User::query()->where('email', 'baru@example.com')->firstOrFail();
        expect($user->role)->toBe(UserRole::Tenant)
            ->and($user->status)->toBe(UserStatus::Pending);
    });

    it('menolak NIK yang bukan 16 digit, telepon tidak valid, dan password tanpa konfirmasi', function () {
        $this->from(route('register'))->post(route('register.store'), [
            'nik' => '12345',
            'name' => 'X',
            'phone' => '08abc',
            'email' => 'x@example.com',
            'password' => 'password123',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['nik', 'phone', 'password']);

        expect(User::query()->count())->toBe(0);
    });

    it('menolak NIK dan email yang sudah terdaftar', function () {
        $existing = User::factory()->create();

        $this->post(route('register.store'), [
            'nik' => $existing->nik,
            'name' => 'Duplikat',
            'phone' => '081234567890',
            'email' => $existing->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['nik', 'email']);
    });
});

describe('Login', function () {
    it('menolak login penyewa berstatus pending dengan pesan menunggu verifikasi', function () {
        $user = User::factory()->pending()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun Anda masih menunggu verifikasi admin. Silakan coba lagi setelah disetujui.']);

        $this->assertGuest();
    });

    it('menolak login penyewa berstatus rejected dengan pesan ditolak', function () {
        $user = User::factory()->rejected()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Pendaftaran akun Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.']);

        $this->assertGuest();
    });

    it('mengizinkan login setelah status penyewa diubah menjadi approved', function () {
        $user = User::factory()->pending()->create();
        $user->update(['status' => UserStatus::Approved]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('tenant.dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    it('mengarahkan admin ke dashboard admin setelah login', function () {
        $admin = User::factory()->admin()->create();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    });

    it('menolak password yang salah', function () {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    });

    it('logout mengakhiri sesi pengguna', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });
});

describe('Hak akses berdasarkan role', function () {
    it('mengarahkan tamu ke halaman login saat membuka dashboard', function () {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('tenant.dashboard'))->assertRedirect(route('login'));
    });

    it('melarang penyewa mengakses route admin', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('melarang admin mengakses route penyewa', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('tenant.dashboard'))
            ->assertForbidden();
    });

    it('menampilkan dashboard sesuai role', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Admin');

        $this->actingAs(User::factory()->create(['name' => 'Budi Penyewa']))
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Halo, Budi Penyewa');
    });

    it('mengarahkan halaman root ke login untuk tamu', function () {
        $this->get('/')->assertRedirect(route('login'));
    });
});
