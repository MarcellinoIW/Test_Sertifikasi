<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreRentalRequest;
use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalPriceCalculator;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// controller untuk sewa garasi oleh penyewa
class RentalController extends Controller
{
    // function constructor untuk memanggil RentalService
    public function __construct(private readonly RentalService $rentalService) {}

    // function untuk menampilkan daftar sewa milik penyewa
    public function index(Request $request): View
    {
        return view('tenant.rentals.index', $this->rentalService->tenantRentals($request->user()));
    }

    // function untuk menampilkan form sewa garasi
    public function create(): View
    {
        return view('tenant.rentals.create', $this->rentalService->formData());
    }

    // function untuk mengambil daftar garasi yang tersedia
    public function availableGarages(Request $request, RentalPriceCalculator $calculator): JsonResponse
    {
        $request->validate([
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'garage_type' => ['required', Rule::enum(GarageType::class)],
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$this->rentalService->earliestStartDate()->toDateString(),
                'before_or_equal:'.$this->rentalService->latestStartDate()->toDateString(),
            ],
            // Maksimal 31 = jumlah hari bulan terpanjang (sewa bulanan).
            'days' => ['required', 'integer', 'min:1', 'max:31'],
        ], [
            'vehicle_type.required' => 'Tipe kendaraan wajib dipilih.',
            'vehicle_type.enum' => 'Tipe kendaraan tidak valid.',
            'garage_type.required' => 'Tipe garasi wajib dipilih.',
            'garage_type.enum' => 'Tipe garasi tidak valid.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date_format' => 'Format tanggal mulai tidak valid.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'start_date.before_or_equal' => 'Tanggal mulai paling lambat '.config('garasi.max_start_days_ahead').' hari dari hari ini.',
            'days.required' => 'Jumlah hari wajib diisi.',
            'days.integer' => 'Jumlah hari harus berupa bilangan bulat.',
            'days.min' => 'Jumlah hari minimal 1 hari.',
            'days.max' => 'Jumlah hari maksimal 31 hari.',
        ]);

        $garages = $this->rentalService->availableGarages(
            $request->enum('vehicle_type', VehicleType::class),
            $request->enum('garage_type', GarageType::class),
            $request->date('start_date', 'Y-m-d'),
            $request->integer('days'),
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

    // function untuk menyimpan data sewa baru
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

    // function untuk menampilkan detail sewa penyewa
    public function show(Request $request, Rental $rental): View
    {
        // Penyewa hanya boleh melihat sewanya sendiri.
        abort_unless($rental->user_id === $request->user()->id, 403);

        $rental->load(['garage', 'vehicle', 'extensions' => fn ($query) => $query->orderBy('created_at')]);

        return view('tenant.rentals.show', ['rental' => $rental]);
    }
}
