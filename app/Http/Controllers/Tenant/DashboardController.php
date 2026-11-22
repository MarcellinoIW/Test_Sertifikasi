<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\RentalExtension;
use Illuminate\Http\Request;
use Illuminate\View\View;

// controller untuk dashboard penyewa
class DashboardController extends Controller
{
    // function untuk menampilkan dashboard penyewa
    public function __invoke(Request $request): View
    {
        $tenant = $request->user();
        $today = today();

        // Total bayar = semua sewa baru + semua perpanjangan milik penyewa.
        $rentalPaid = (float) $tenant->rentals()->sum('total_cost');
        $extensionPaid = (float) RentalExtension::query()
            ->whereHas('rental', fn ($query) => $query->where('user_id', $tenant->id))
            ->sum('cost');

        // Sewa yang belum selesai: aktif hari ini + booking tanggal depan.
        $current = $tenant->rentals()
            ->notFinishedOn($today)
            ->with(['garage', 'vehicle'])
            ->orderBy('start_date')
            ->get();

        $activeCount = $current->filter(fn (Rental $rental) => $rental->statusOn($today) === 'active')->count();

        return view('tenant.dashboard', [
            'current_rentals' => $current,
            'active_count' => $activeCount,
            'upcoming_count' => $current->count() - $activeCount,
            'total_rentals' => $tenant->rentals()->count(),
            'total_paid' => $rentalPaid + $extensionPaid,
            'today' => $today,
        ]);
    }
}
