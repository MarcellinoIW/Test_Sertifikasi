<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// controller untuk registrasi penyewa
class RegisterController extends Controller
{
    // function untuk menampilkan halaman register
    public function create(): View
    {
        return view('auth.register');
    }

    // function untuk menyimpan data pendaftaran penyewa
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $attributes = [
                'nik' => $data['nik'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Tenant,
                'status' => UserStatus::Pending,
            ];

            // Akun ditolak yang memakai email atau NIK yang sama (bisa dua akun berbeda).
            $rejected = User::query()
                ->where('status', UserStatus::Rejected)
                ->where(fn ($query) => $query->where('email', $data['email'])->orWhere('nik', $data['nik']))
                ->lockForUpdate()
                ->get();

            if ($rejected->isEmpty()) {
                $user = User::query()->create($attributes);
                $user->verifications()->create(['action' => VerificationAction::Registered]);

                return;
            }

            // Utamakan akun yang emailnya sama
            $user = $rejected->firstWhere('email', $data['email']) ?? $rejected->first();

            $rejected->reject(fn (User $other) => $other->is($user))
                ->each(fn (User $other) => $other->delete());

            $user->fill($attributes);
            $user->created_at = now();
            $user->save();

            // Riwayat penolakan sebelumnya tetap tersimpan; catat kejadian daftar ulang.
            $user->verifications()->create(['action' => VerificationAction::ReRegistered]);
        });

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil. Akun Anda akan aktif setelah diverifikasi admin.');
    }
}
