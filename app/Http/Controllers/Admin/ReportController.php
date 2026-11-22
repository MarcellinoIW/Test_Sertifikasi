<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// controller untuk laporan sewa
class ReportController extends Controller
{
    // daftar jenis laporan beserta label dan ikonnya
    public const TYPES = [
        'monthly' => ['Per Bulan', 'calendar3'],
        'daily' => ['Per Hari', 'calendar-day'],
        'garage_type' => ['Per Tipe Garasi', 'grid-3x3-gap'],
        'tenant' => ['Per Penyewa', 'people'],
    ];

    // function constructor untuk memanggil ReportService
    public function __construct(private readonly ReportService $reportService) {}

    // function untuk menampilkan laporan sesuai jenis yang dipilih
    public function index(Request $request): View
    {
        $request->validate([
            'type' => ['nullable', Rule::in(array_keys(self::TYPES))],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'type.in' => 'Jenis laporan tidak valid.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun minimal :min.',
            'year.max' => 'Tahun maksimal :max.',
            'month.integer' => 'Bulan tidak valid.',
            'month.min' => 'Bulan tidak valid.',
            'month.max' => 'Bulan tidak valid.',
            'from.date_format' => 'Format tanggal awal tidak valid.',
            'to.date_format' => 'Format tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);

        // Nilai default: laporan per bulan, tahun & bulan berjalan.
        $type = $request->input('type') ?: 'monthly';
        $year = $request->filled('year') ? $request->integer('year') : (int) now()->year;
        $month = $request->filled('month') ? $request->integer('month') : (int) now()->month;
        $from = $request->filled('from') ? CarbonImmutable::createFromFormat('!Y-m-d', $request->input('from')) : null;
        $to = $request->filled('to') ? CarbonImmutable::createFromFormat('!Y-m-d', $request->input('to')) : null;

        $report = match ($type) {
            'monthly' => $this->reportService->monthly($year),
            'daily' => $this->reportService->daily($year, $month),
            'garage_type' => $this->reportService->byGarageType($from, $to),
            'tenant' => $this->reportService->byTenant($from, $to),
        };

        return view('admin.reports.index', [
            'report' => $report,
            'type' => $type,
            'types' => self::TYPES,
            'year' => $year,
            'month' => $month,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'years' => $this->reportService->availableYears(),
        ]);
    }
}
