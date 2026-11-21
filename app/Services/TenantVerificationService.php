<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Verifikasi pendaftar penyewa oleh admin (KTP dicek secara verbal).
 */
class TenantVerificationService
{
    /**
     * Daftar penyewa berdasarkan status, terlama mendaftar lebih dulu.
     *
     * @param  UserStatus  $status  Status yang ditampilkan.
     * @param  int  $perPage  Jumlah baris per halaman.
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserStatus $status = UserStatus::Pending, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->tenants()
            ->withStatus($status)
            ->orderBy('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Jumlah penyewa per status, untuk badge pada tab.
     *
     * @return array<string, int> Kunci = nilai UserStatus.
     */
    public function countByStatus(): array
    {
        $counts = User::query()
            ->tenants()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];
        foreach (UserStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    /**
     * Menerima pendaftar sehingga bisa login.
     *
     * @param  User  $tenant  Pendaftar berstatus pending.
     * @return User
     *
     * @throws BusinessRuleException Jika bukan penyewa atau status bukan pending.
     */
    public function approve(User $tenant): User
    {
        return $this->changeStatus($tenant, UserStatus::Approved);
    }

    /**
     * Menolak pendaftar sehingga tidak bisa login.
     *
     * @param  User  $tenant  Pendaftar berstatus pending.
     * @return User
     *
     * @throws BusinessRuleException Jika bukan penyewa atau status bukan pending.
     */
    public function reject(User $tenant): User
    {
        return $this->changeStatus($tenant, UserStatus::Rejected);
    }

    /**
     * Mengubah status pendaftar pending.
     *
     * @param  User  $tenant  Pendaftar.
     * @param  UserStatus  $status  Status baru.
     * @return User
     *
     * @throws BusinessRuleException Jika bukan penyewa atau status bukan pending.
     */
    private function changeStatus(User $tenant, UserStatus $status): User
    {
        if (! $tenant->isTenant()) {
            throw new BusinessRuleException('Pengguna ini bukan penyewa.');
        }

        if ($tenant->status !== UserStatus::Pending) {
            throw new BusinessRuleException("Pendaftar {$tenant->name} sudah diverifikasi sebelumnya ({$tenant->status->label()}).");
        }

        $tenant->update(['status' => $status]);

        return $tenant;
    }
}
