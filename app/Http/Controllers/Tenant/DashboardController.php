<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard penyewa.
 */
class DashboardController extends Controller
{
    /**
     * Tampilkan sewa aktif dan ringkasan pembayaran penyewa.
     *
     * @param  Request  $request  Request saat ini.
     * @param  DashboardService  $dashboardService  Service ringkasan dashboard.
     * @return View
     */
    public function __invoke(Request $request, DashboardService $dashboardService): View
    {
        return view('tenant.dashboard', $dashboardService->tenantSummary($request->user()));
    }
}
