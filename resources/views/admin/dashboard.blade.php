@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('header', 'Dashboard Admin')

@section('content')
    @php
        $stats = [
            ['Total Garasi', $garages_total, 'house-door', 'primary', 'admin.garages.index'],
            ['Garasi Tersedia', $garages_available, 'check2-circle', 'success', 'admin.garages.index'],
            ['Sewa Aktif Hari Ini', $active_rentals, 'calendar-check', 'info', 'admin.extensions.index'],
            ['Penyewa Menunggu', $pending_tenants, 'hourglass-split', 'warning', 'admin.tenants.index'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($stats as [$label, $value, $icon, $color, $route])
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">{{ $label }}</div>
                            <div class="fs-3 fw-semibold">{{ number_format($value, 0, ',', '.') }}</div>
                            @if (Route::has($route))
                                <a href="{{ route($route) }}" class="small stretched-link">Lihat</a>
                            @endif
                        </div>
                        <i class="bi bi-{{ $icon }} text-{{ $color }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-grid-3x3-gap me-1"></i> Garasi per Tipe
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tipe</th>
                                <th class="text-end">Tersedia</th>
                                <th class="text-end">Terpakai</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($garages as $row)
                                <tr>
                                    <td>
                                        <span class="badge text-bg-dark me-1">{{ $row['type']->prefix() }}</span>
                                        {{ $row['type']->label() }}
                                    </td>
                                    <td class="text-end text-success">{{ $row['available'] }}</td>
                                    <td class="text-end text-secondary">{{ $row['used'] }}</td>
                                    <td class="text-end fw-semibold">{{ $row['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-clock-history me-1"></i> Sewa Terbaru
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Garasi</th>
                                <th>Penyewa</th>
                                <th>Periode</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latest_rentals as $rental)
                                <tr>
                                    <td><span class="badge text-bg-primary">{{ $rental->garage->code }}</span></td>
                                    <td>
                                        {{ $rental->user->name }}
                                        <div class="small text-muted">{{ $rental->vehicle->displayName() }}</div>
                                    </td>
                                    <td class="small">@tanggal($rental->start_date) – @tanggal($rental->end_date)</td>
                                    <td class="text-end">@rupiah($rental->total_cost)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada data sewa.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
