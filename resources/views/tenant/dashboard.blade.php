@extends('layouts.app')

@section('title', 'Dashboard Penyewa')
@section('header', 'Halo, '.auth()->user()->name)

@section('actions')
    @if (Route::has('tenant.rentals.create'))
        <a href="{{ route('tenant.rentals.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Sewa Garasi Baru
        </a>
    @endif
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Sewa Aktif</div>
                        <div class="fs-3 fw-semibold">{{ $active_count }}</div>
                        @if ($upcoming_count > 0)
                            <div class="small text-info">+{{ $upcoming_count }} akan datang</div>
                        @endif
                    </div>
                    <i class="bi bi-calendar-check text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Transaksi Sewa</div>
                        <div class="fs-3 fw-semibold">{{ $total_rentals }}</div>
                    </div>
                    <i class="bi bi-receipt text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Pembayaran</div>
                        <div class="fs-4 fw-semibold">@rupiah($total_paid)</div>
                    </div>
                    <i class="bi bi-wallet2 text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-house-check me-1"></i> Sewa Aktif &amp; Akan Datang</span>
            @if (Route::has('tenant.rentals.index'))
                <a href="{{ route('tenant.rentals.index') }}" class="small">Lihat semua sewa</a>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Garasi</th>
                        <th>Kendaraan</th>
                        <th>Jenis</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Status</th>
                        <th class="text-end">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($current_rentals as $rental)
                        @php $status = $rental->statusOn($today); @endphp
                        <tr>
                            <td>
                                <span class="badge text-bg-primary">{{ $rental->garage->code }}</span>
                                <div class="small text-muted">{{ $rental->garage->type->label() }}</div>
                            </td>
                            <td>{{ $rental->vehicle->displayName() }}</td>
                            <td>{{ $rental->rental_type->label() }}</td>
                            <td>@tanggal($rental->start_date)</td>
                            <td>@tanggal($rental->end_date)</td>
                            <td><x-rental-status :status="$status" /></td>
                            <td class="text-end small">
                                @if ($status === 'upcoming')
                                    mulai {{ (int) $today->diffInDays($rental->start_date) }} hari lagi
                                @else
                                    sisa {{ (int) $today->diffInDays($rental->end_date) }} hari
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Anda belum memiliki sewa aktif atau yang akan datang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
