<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Logika bisnis autentikasi: registrasi penyewa dan login dengan
 * pengecekan status verifikasi.
 */
class AuthService
{
    /**
     * Mendaftarkan penyewa baru dengan status awal pending.
     *
     * @param  array{nik: string, name: string, phone: string, email: string, password: string}  $data  Data registrasi tervalidasi.
     * @return User Penyewa yang baru dibuat.
     */
    public function registerTenant(array $data): User
    {
        return User::query()->create([
            'nik' => $data['nik'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Tenant,
            'status' => UserStatus::Pending,
        ]);
    }

    /**
     * Login pengguna. Kredensial dicek lebih dulu, lalu status akun:
     * hanya admin dan penyewa approved yang boleh masuk.
     *
     * @param  string  $email  Email pengguna.
     * @param  string  $password  Password polos.
     * @param  bool  $remember  Simpan sesi login ("ingat saya").
     * @return User Pengguna yang berhasil login.
     *
     * @throws ValidationException Jika kredensial salah atau akun belum/tidak disetujui.
     */
    public function login(string $email, string $password, bool $remember = false): User
    {
        $credentials = ['email' => $email, 'password' => $password];

        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        /** @var User $user */
        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        if (! $user->isApproved()) {
            throw ValidationException::withMessages([
                'email' => $this->statusMessage($user->status),
            ]);
        }

        Auth::login($user, $remember);

        return $user;
    }

    /**
     * Pesan penolakan login sesuai status akun.
     *
     * @param  UserStatus  $status  Status akun penyewa.
     * @return string
     */
    private function statusMessage(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::Pending => 'Akun Anda masih menunggu verifikasi admin. Silakan coba lagi setelah disetujui.',
            UserStatus::Rejected => 'Pendaftaran akun Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.',
            UserStatus::Approved => '',
        };
    }
}
