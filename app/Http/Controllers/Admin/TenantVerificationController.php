<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Enums\VerificationAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// controller untuk verifikasi pendaftaran penyewa
class TenantVerificationController extends Controller
{
    // function untuk menampilkan daftar penyewa yang mendaftar
    public function index(Request $request): View
    {
        $request->validate(
            ['status' => ['nullable', Rule::enum(UserStatus::class)]],
            ['status.enum' => 'Status penyewa tidak valid.'],
        );

        $status = $request->enum('status', UserStatus::class) ?? UserStatus::Pending;
        $rejected = fn ($query) => $query->where('action', VerificationAction::Rejected);

        $tenants = User::query()
            ->tenants()
            ->withStatus($status)
            ->with(['verifications.admin'])
            ->withCount(['verifications as rejection_count' => $rejected])
            ->withMax(['verifications as last_rejected_at' => $rejected], 'created_at')
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        // Jumlah penyewa per status untuk badge di tab.
        $totals = User::query()->tenants()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [];
        foreach (UserStatus::cases() as $case) {
            $counts[$case->value] = (int) ($totals[$case->value] ?? 0);
        }

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'status' => $status,
            'statuses' => UserStatus::cases(),
            'counts' => $counts,
        ]);
    }

    // function untuk menerima pendaftaran penyewa
    public function approve(Request $request, User $tenant): RedirectResponse
    {
        return $this->changeStatus($request->user(), $tenant, UserStatus::Approved, VerificationAction::Approved)
            ?? back()->with('success', "Pendaftar {$tenant->name} diterima dan sekarang bisa login.");
    }

    // function untuk menolak pendaftaran penyewa
    public function reject(Request $request, User $tenant): RedirectResponse
    {
        return $this->changeStatus($request->user(), $tenant, UserStatus::Rejected, VerificationAction::Rejected)
            ?? back()->with('success', "Pendaftar {$tenant->name} ditolak.");
    }

    // function untuk mengubah status penyewa dan mencatat riwayatnya
    private function changeStatus(User $admin, User $tenant, UserStatus $status, VerificationAction $action): ?RedirectResponse
    {
        if (! $tenant->isTenant()) {
            return back()->with('error', 'Pengguna ini bukan penyewa.');
        }

        if ($tenant->status !== UserStatus::Pending) {
            return back()->with('error', "Pendaftar {$tenant->name} sudah diverifikasi sebelumnya ({$tenant->status->label()}).");
        }

        DB::transaction(function () use ($admin, $tenant, $status, $action) {
            $tenant->update(['status' => $status]);
            $tenant->verifications()->create(['action' => $action, 'admin_id' => $admin->id]);
        });

        return null;
    }
}
