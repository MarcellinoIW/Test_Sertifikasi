<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

/**
 * Dashboard admin.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan ringkasan garasi, pendaftar, dan sewa terbaru.
     *
     * @param  DashboardService  $dashboardService  Service ringkasan dashboard.
     * @return View
     */
    public function __invoke(DashboardService $dashboardService): View
    {
        return view('admin.dashboard', $dashboardService->adminSummary());
    }
}
