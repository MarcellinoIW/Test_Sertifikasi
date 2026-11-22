<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// controller untuk login dan logout
class LoginController extends Controller
{
    // function untuk menampilkan halaman login
    public function create(): View
    {
        return view('auth.login');
    }

    // function untuk proses login
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->only('email', 'password');

        if (! Auth::validate($credentials)) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        $user = Auth::getProvider()->retrieveByCredentials($credentials);

        if (! $user->isApproved()) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages([
                'email' => $user->status === UserStatus::Pending
                    ? 'Akun Anda masih menunggu verifikasi admin. Silakan coba lagi setelah disetujui.'
                    : 'Pendaftaran akun Anda ditolak. Silakan hubungi admin untuk informasi lebih lanjut.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->clearRateLimiter();
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())
            ->with('success', "Selamat datang, {$user->name}!");
    }

    // function untuk logout
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
