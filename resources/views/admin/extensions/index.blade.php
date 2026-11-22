@extends('layouts.app')

@section('title', 'Perpanjang Sewa')
@section('header', 'Perpanjang Sewa')

@section('content')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.extensions.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-5">
                    <label for="q" class="form-label small mb-1">Cari sewa aktif</label>
                    <input type="search" name="q" id="q" value="{{ $search }}" maxlength="50"
                           class="form-control @error('q') is-invalid @enderror"
                           placeholder="Kode garasi, nama penyewa, atau nomor plat">
                    @error('q')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    @if ($search)
                        <a href="{{ route('admin.extensions.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-calendar-check me-1"></i> Garasi yang Sedang Disewa Hari Ini</span>
            <span class="small text-muted">{{ $rentals->total() }} sewa aktif</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Garasi</th>
                        <th>Penyewa</th>
                        <th>Kendaraan</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rentals as $rental)
                        <tr>
                            <td>
                                <span class="badge text-bg-primary fs-6">{{ $rental->garage->code }}</span>
                                <div class="small text-muted">{{ $rental->garage->type->label() }}</div>
                            </td>
                            <td>
                                {{ $rental->user->name }}
                                <div class="small text-muted">{{ $rental->user->phone }}</div>
                            </td>
                            <td>{{ $rental->vehicle->displayName() }}</td>
                            <td>@tanggal($rental->start_date)</td>
                            <td>
                                @tanggal($rental->end_date)
                                @if ($rental->extensions_count > 0)
                                    <span class="badge text-bg-info ms-1" title="Sudah pernah diperpanjang">
                                        <i class="bi bi-calendar-plus"></i> {{ $rental->extensions_count }}×
                                    </span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.rentals.show', $rental) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>
                                <a href="{{ route('admin.extensions.create', $rental) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-calendar-plus me-1"></i> Perpanjang
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                {{ $search ? 'Tidak ada sewa aktif yang cocok dengan pencarian.' : 'Tidak ada garasi yang sedang disewa hari ini.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rentals->hasPages())
            <div class="card-footer bg-white">{{ $rentals->links() }}</div>
        @endif
    </div>
@endsection
