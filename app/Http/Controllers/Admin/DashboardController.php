<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Services\GarageService;
use Illuminate\View\View;

// controller untuk dashboard admin
class DashboardController extends Controller
{
    // function untuk menampilkan halaman dashboard admin
    public function __invoke(GarageService $garageService): View
    {
        $today = today();
        $garageService->syncStatus($today);

        // Jumlah garasi per tipe & status dalam satu query .
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

        return view('admin.dashboard', [
            'garages' => $garages,
            'garages_total' => array_sum(array_column($garages, 'total')),
            'garages_available' => array_sum(array_column($garages, 'available')),
            'pending_tenants' => User::query()->tenants()->withStatus(UserStatus::Pending)->count(),
            'active_rentals' => Rental::query()->activeOn($today)->count(),
            'latest_rentals' => Rental::query()
                ->with(['user', 'garage', 'vehicle'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
