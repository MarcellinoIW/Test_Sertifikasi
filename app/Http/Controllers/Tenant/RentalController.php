<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AvailableGaragesRequest;
use App\Http\Requests\Tenant\StoreRentalRequest;
use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalPriceCalculator;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Sewa garasi oleh penyewa: form sewa baru, simpan, "Sewa Saya", dan detail.
 */
class RentalController extends Controller
{
    /**
     * @param  RentalService  $rentalService  Service sewa garasi.
     */
    public function __construct(private readonly RentalService $rentalService) {}

    /**
     * Halaman "Sewa Saya": sewa aktif dan riwayat, termasuk perpanjangan.
     *
     * @param  Request  $request  Request saat ini.
     * @return View
     */
    public function index(Request $request): View
    {
        return view('tenant.rentals.index', $this->rentalService->tenantRentals($request->user()));
    }

    /**
     * Form sewa baru (status garasi disinkronkan lebih dulu).
     *
     * @return View
     */
    public function create(): View
    {
        return view('tenant.rentals.create', $this->rentalService->formData());
    }

    /**
     * Endpoint JSON: garasi tersedia hari ini untuk tipe kendaraan & tipe garasi.
     *
     * @param  AvailableGaragesRequest  $request  Parameter tervalidasi.
     * @param  RentalPriceCalculator  $calculator  Untuk menampilkan harga bulanan.
     * @return JsonResponse
     */
    public function availableGarages(AvailableGaragesRequest $request, RentalPriceCalculator $calculator): JsonResponse
    {
        $garages = $this->rentalService->availableGarages(
            $request->enum('vehicle_type', VehicleType::class),
            $request->enum('garage_type', GarageType::class),
        );

        return response()->json([
            'data' => $garages->map(fn (Garage $garage) => [
                'id' => $garage->id,
                'code' => $garage->code,
                'type' => $garage->type->value,
                'daily_price' => (float) $garage->daily_price,
                'monthly_price' => $calculator->monthlyPrice((float) $garage->daily_price),
                'electricity_price_per_kwh' => $garage->electricity_price_per_kwh !== null
                    ? (float) $garage->electricity_price_per_kwh
                    : null,
            ])->values(),
        ]);
    }

    /**
     * Simpan sewa baru. Biaya dihitung ulang di server.
     *
     * @param  StoreRentalRequest  $request  Request tervalidasi.
     * @return RedirectResponse
     */
    public function store(StoreRentalRequest $request): RedirectResponse
    {
        $rental = $this->rentalService->create(
            $request->user(),
            $request->safe()->except('payment_proof'),
            $request->file('payment_proof'),
        );

        return redirect()
            ->route('tenant.rentals.show', $rental)
            ->with('success', "Sewa garasi {$rental->garage->code} berhasil disimpan.");
    }

    /**
     * Detail satu sewa milik penyewa.
     *
     * @param  Rental  $rental  Rental yang dilihat.
     * @return View
     */
    public function show(Rental $rental): View
    {
        Gate::authorize('view', $rental);

        $rental->load(['garage', 'vehicle', 'extensions' => fn ($query) => $query->orderBy('created_at')]);

        return view('tenant.rentals.show', ['rental' => $rental]);
    }
}
