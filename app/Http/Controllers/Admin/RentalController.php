<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// controller untuk data sewa di halaman admin
class RentalController extends Controller
{
    // function constructor untuk memanggil RentalService
    public function __construct(private readonly RentalService $rentalService) {}

    // function untuk menampilkan daftar semua data sewa
    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Rental::STATUSES))],
            'q' => ['nullable', 'string', 'max:50'],
        ], [
            'status.in' => 'Filter status sewa tidak valid.',
            'q.max' => 'Kata kunci pencarian maksimal 50 karakter.',
        ]);

        $status = $request->input('status') ?: null;

        return view('admin.rentals.index', [
            'rentals' => $this->rentalService->paginateForAdmin($status, $request->input('q')),
            'status' => $status,
            'statuses' => Rental::STATUSES,
            'search' => $request->input('q'),
            'today' => today(),
        ]);
    }

    // function untuk menampilkan detail sewa
    public function show(Rental $rental): View
    {
        $rental->load([
            'garage',
            'user',
            'vehicle',
            'extensions' => fn ($query) => $query->with('admin')->orderBy('created_at'),
        ]);

        return view('admin.rentals.show', [
            'rental' => $rental,
            'status' => $rental->statusOn(today()),
        ]);
    }
}
