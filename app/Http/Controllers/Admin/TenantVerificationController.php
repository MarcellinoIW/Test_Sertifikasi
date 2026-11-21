<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantFilterRequest;
use App\Models\User;
use App\Services\TenantVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Verifikasi pendaftar penyewa: daftar, terima, tolak.
 */
class TenantVerificationController extends Controller
{
    /**
     * @param  TenantVerificationService  $verificationService  Service verifikasi penyewa.
     */
    public function __construct(private readonly TenantVerificationService $verificationService) {}

    /**
     * Daftar penyewa per status (default: pending).
     *
     * @param  TenantFilterRequest  $request  Filter tervalidasi.
     * @return View
     */
    public function index(TenantFilterRequest $request): View
    {
        $status = $request->tenantStatus();

        return view('admin.tenants.index', [
            'tenants' => $this->verificationService->paginate($status),
            'status' => $status,
            'statuses' => UserStatus::cases(),
            'counts' => $this->verificationService->countByStatus(),
        ]);
    }

    /**
     * Terima pendaftar.
     *
     * @param  User  $tenant  Pendaftar.
     * @return RedirectResponse
     */
    public function approve(User $tenant): RedirectResponse
    {
        $this->verificationService->approve($tenant);

        return back()->with('success', "Pendaftar {$tenant->name} diterima dan sekarang bisa login.");
    }

    /**
     * Tolak pendaftar.
     *
     * @param  User  $tenant  Pendaftar.
     * @return RedirectResponse
     */
    public function reject(User $tenant): RedirectResponse
    {
        $this->verificationService->reject($tenant);

        return back()->with('success', "Pendaftar {$tenant->name} ditolak.");
    }
}
