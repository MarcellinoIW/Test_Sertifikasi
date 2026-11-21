<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Menangani login dan logout pengguna.
 */
class LoginController extends Controller
{
    /**
     * @param  AuthService  $authService  Service autentikasi.
     */
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Tampilkan form login.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login, lalu arahkan ke dashboard sesuai role.
     *
     * @param  LoginRequest  $request  Request login tervalidasi.
     * @return RedirectResponse
     *
     * @throws ValidationException Jika login gagal atau terkena rate limit.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $user = $this->authService->login(
                $request->string('email'),
                $request->string('password'),
                $request->boolean('remember'),
            );
        } catch (ValidationException $e) {
            $request->hitRateLimiter();

            throw $e;
        }

        $request->clearRateLimiter();
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())
            ->with('success', "Selamat datang, {$user->name}!");
    }

    /**
     * Logout dan hapus sesi.
     *
     * @param  Request  $request  Request saat ini.
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }
}
