<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GarageFilterRequest;
use App\Http\Requests\Admin\StoreGarageBatchRequest;
use App\Services\GarageCodeGenerator;
use App\Services\GarageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Pengelolaan garasi oleh admin: daftar dan pembuatan batch.
 */
class GarageController extends Controller
{
    /**
     * @param  GarageService  $garageService  Service garasi.
     */
    public function __construct(private readonly GarageService $garageService) {}

    /**
     * Daftar garasi dengan filter tipe dan status.
     *
     * @param  GarageFilterRequest  $request  Filter tervalidasi.
     * @return View
     */
    public function index(GarageFilterRequest $request): View
    {
        return view('admin.garages.index', [
            'garages' => $this->garageService->paginate($request->garageType(), $request->garageStatus()),
            'types' => GarageType::cases(),
            'statuses' => GarageStatus::cases(),
        ]);
    }

    /**
     * Form pembuatan garasi batch.
     *
     * @param  GarageCodeGenerator  $codeGenerator  Untuk preview kode berikutnya.
     * @return View
     */
    public function create(GarageCodeGenerator $codeGenerator): View
    {
        return view('admin.garages.create', [
            'types' => GarageType::cases(),
            'lastNumbers' => $codeGenerator->lastNumbers(),
            'maxNumber' => (int) config('garasi.max_garages_per_batch'),
        ]);
    }

    /**
     * Simpan garasi batch.
     *
     * @param  StoreGarageBatchRequest  $request  Request tervalidasi.
     * @return RedirectResponse
     */
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
