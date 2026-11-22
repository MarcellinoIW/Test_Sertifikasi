<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRentalExtensionRequest;
use App\Models\Rental;
use App\Services\RentalExtensionService;
use App\Services\RentalPriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// controller untuk perpanjangan sewa
class RentalExtensionController extends Controller
{
    // function constructor untuk memanggil RentalExtensionService
    public function __construct(private readonly RentalExtensionService $extensionService) {}

    // function untuk menampilkan daftar sewa yang masih aktif
    public function index(Request $request): View
    {
        $request->validate(
            ['q' => ['nullable', 'string', 'max:50']],
            ['q.max' => 'Kata kunci pencarian maksimal 50 karakter.'],
        );

        return view('admin.extensions.index', [
            'rentals' => $this->extensionService->activeRentals($request->input('q')),
            'search' => $request->input('q'),
        ]);
    }

    // function untuk menampilkan form perpanjangan sewa
    public function create(Rental $rental, RentalPriceCalculator $calculator): View|RedirectResponse
    {
        if (! $rental->isActiveOn(today())) {
            return redirect()
                ->route('admin.extensions.index')
                ->with('error', 'Hanya sewa yang sedang aktif hari ini yang dapat diperpanjang.');
        }

        $rental->load(['garage', 'user', 'vehicle', 'extensions' => fn ($query) => $query->with('admin')->orderBy('created_at')]);

        return view('admin.extensions.create', [
            'rental' => $rental,
            'monthlyMultiplier' => $calculator->monthlyMultiplier(),
            'monthlyPrice' => $calculator->monthlyPrice((float) $rental->daily_price),
        ]);
    }

    // function untuk menghitung pratinjau biaya perpanjangan
    public function preview(Request $request, Rental $rental): JsonResponse
    {
        $request->validate([
            'additional_days' => ['required', 'integer', 'min:1', 'max:'.StoreRentalExtensionRequest::MAX_DAYS],
        ], [
            'additional_days.required' => 'Tambahan hari wajib diisi.',
            'additional_days.integer' => 'Tambahan hari harus berupa bilangan bulat.',
            'additional_days.min' => 'Tambahan hari minimal 1 hari.',
            'additional_days.max' => 'Tambahan hari terlalu besar.',
        ]);

        $breakdown = $this->extensionService->preview($rental, $request->integer('additional_days'));

        return response()->json([
            'data' => [
                ...$breakdown,
                'old_end_date' => $breakdown['old_end_date']->toDateString(),
                'new_end_date' => $breakdown['new_end_date']->toDateString(),
                'new_end_date_label' => $breakdown['new_end_date']->translatedFormat('d M Y'),
                'conflict_message' => $this->extensionService->conflictMessage($rental, $breakdown),
            ],
        ]);
    }

    // function untuk menyimpan data perpanjangan sewa
    public function store(StoreRentalExtensionRequest $request, Rental $rental): RedirectResponse
    {
        if (! $rental->isActiveOn(today())) {
            return back()->with('error', 'Hanya sewa yang sedang aktif hari ini yang dapat diperpanjang.');
        }

        $extension = $this->extensionService->extend(
            $rental,
            $request->user(),
            $request->additionalDays(),
            $request->file('payment_proof'),
        );

        // Arahkan ke detail sewa agar bukti bayar yang baru diunggah langsung terlihat.
        return redirect()
            ->route('admin.rentals.show', $rental)
            ->with('success', sprintf(
                'Sewa garasi %s diperpanjang %d hari sampai %s. Biaya %s.',
                $rental->garage->code,
                $extension->additional_days,
                $extension->new_end_date->translatedFormat('d M Y'),
                'Rp '.number_format((float) $extension->cost, 0, ',', '.'),
            ));
    }
}
