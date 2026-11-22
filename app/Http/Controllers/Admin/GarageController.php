<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGarageBatchRequest;
use App\Services\GarageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// controller untuk kelola data garasi
class GarageController extends Controller
{
    // function constructor untuk memanggil GarageService
    public function __construct(private readonly GarageService $garageService) {}

    // function untuk menampilkan daftar garasi beserta filternya
    public function index(Request $request): View
    {
        $request->validate([
            'type' => ['nullable', Rule::enum(GarageType::class)],
            'status' => ['nullable', Rule::enum(GarageStatus::class)],
        ], [
            'type.enum' => 'Filter tipe garasi tidak valid.',
            'status.enum' => 'Filter status garasi tidak valid.',
        ]);

        return view('admin.garages.index', [
            'garages' => $this->garageService->paginate(
                $request->enum('type', GarageType::class),
                $request->enum('status', GarageStatus::class),
            ),
            'types' => GarageType::cases(),
            'statuses' => GarageStatus::cases(),
        ]);
    }

    // function untuk menampilkan form tambah garasi
    public function create(): View
    {
        return view('admin.garages.create', [
            'types' => GarageType::cases(),
            'lastNumbers' => $this->garageService->lastNumbers(),
            'maxNumber' => (int) config('garasi.max_garages_per_batch'),
        ]);
    }

    // function untuk menyimpan data garasi baru
    public function store(StoreGarageBatchRequest $request): RedirectResponse
    {
        $garages = $this->garageService->createBatch(
            $request->garageType(),
            $request->integer('quantity'),
            $request->float('daily_price'),
            $request->electricityPrice(),
        );

        $range = $garages->count() === 1
            ? $garages->first()->code
            : "{$garages->first()->code} – {$garages->last()->code}";

        return redirect()
            ->route('admin.garages.index', ['type' => $request->garageType()->value])
            ->with('success', "{$garages->count()} garasi berhasil dibuat ({$range}).");
    }
}
