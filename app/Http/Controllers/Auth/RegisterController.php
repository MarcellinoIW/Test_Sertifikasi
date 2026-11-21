<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Menangani registrasi mandiri penyewa.
 */
class RegisterController extends Controller
{
    /**
     * @param  AuthService  $authService  Service autentikasi.
     */
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Tampilkan form registrasi.
     *
     * @return View
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Simpan pendaftar baru (status pending). Pendaftar tidak langsung
     * login; harus menunggu verifikasi admin.
     *
     * @param  RegisterRequest  $request  Request registrasi tervalidasi.
     * @return RedirectResponse
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $this->authService->registerTenant($request->validated());

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil. Akun Anda akan aktif setelah diverifikasi admin.');
    }
}
