<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\UserStatus;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Menyusun ringkasan data untuk dashboard admin dan penyewa.
 */
class DashboardService
{
    /**
     * @param  GarageStatusService  $garageStatusService  Service sinkronisasi status garasi.
     */
    public function __construct(private readonly GarageStatusService $garageStatusService) {}

    /**
     * Ringkasan untuk dashboard admin.
     *
     * @param  CarbonInterface|null  $date  Tanggal acuan; default hari ini.
     * @return array{
     *     garages: array<string, array{type: GarageType, total: int, available: int, used: int}>,
     *     garages_total: int,
     *     garages_available: int,
     *     pending_tenants: int,
     *     active_rentals: int,
     *     latest_rentals: Collection<int, Rental>
     * }
     */
    public function adminSummary(?CarbonInterface $date = null): array
    {
        $date ??= today();
        $this->garageStatusService->sync($date);

        $counts = Garage::query()
            ->selectRaw('type, status, COUNT(*) as total')
            ->groupBy('type', 'status')
            ->get();

        $garages = [];
        foreach (GarageType::cases() as $type) {
            $ofType = $counts->where('type', $type);
            $available = (int) $ofType->where('status', GarageStatus::Available)->sum('total');
            $used = (int) $ofType->where('status', GarageStatus::Used)->sum('total');

            $garages[$type->value] = [
                'type' => $type,
                'total' => $available + $used,
                'available' => $available,
                'used' => $used,
            ];
        }

        return [
            'garages' => $garages,
            'garages_total' => array_sum(array_column($garages, 'total')),
            'garages_available' => array_sum(array_column($garages, 'available')),
            'pending_tenants' => User::query()->tenants()->withStatus(UserStatus::Pending)->count(),
            'active_rentals' => Rental::query()->activeOn($date)->count(),
            'latest_rentals' => Rental::query()
                ->with(['user', 'garage', 'vehicle'])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Ringkasan untuk dashboard penyewa.
     *
     * @param  User  $tenant  Penyewa yang sedang login.
     * @param  CarbonInterface|null  $date  Tanggal acuan; default hari ini.
     * @return array{
     *     active_rentals: Collection<int, Rental>,
     *     total_rentals: int,
     *     total_paid: float
     * }
     */
    public function tenantSummary(User $tenant, ?CarbonInterface $date = null): array
    {
        $date ??= today();

        $rentalPaid = (float) $tenant->rentals()->sum('total_cost');
        $extensionPaid = (float) RentalExtension::query()
            ->whereHas('rental', fn ($query) => $query->where('user_id', $tenant->id))
            ->sum('cost');

        return [
            'active_rentals' => $tenant->rentals()
                ->activeOn($date)
                ->with(['garage', 'vehicle'])
                ->orderBy('end_date')
                ->get(),
            'total_rentals' => $tenant->rentals()->count(),
            'total_paid' => $rentalPaid + $extensionPaid,
        ];
    }
}
