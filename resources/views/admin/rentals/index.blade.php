@extends('layouts.app')

@section('title', 'Data Sewa')
@section('header', 'Data Sewa')

@section('content')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.rentals.index') }}" class="row g-2 align-items-end">
                <div class="col-sm-4 col-md-3">
                    <label for="status" class="form-label small mb-1">Status</label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-8 col-md-5">
                    <label for="q" class="form-label small mb-1">Cari</label>
                    <input type="search" name="q" id="q" value="{{ $search }}" maxlength="50"
                           class="form-control @error('q') is-invalid @enderror"
                           placeholder="Kode garasi, nama/email penyewa, atau nomor plat">
                    @error('q')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                    @if ($status || $search)
                        <a href="{{ route('admin.rentals.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-receipt me-1"></i> Seluruh Sewa</span>
            <span class="small text-muted">{{ $rentals->total() }} sewa</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Garasi</th>
                        <th>Penyewa</th>
                        <th>Kendaraan</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th class="text-end">Total Bayar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rentals as $rental)
                        @php $rowStatus = $rental->statusOn($today); @endphp
                        <tr>
                            <td>
                                <span class="badge text-bg-primary">{{ $rental->garage->code }}</span>
                                <div class="small text-muted">{{ $rental->garage->type->label() }}</div>
                            </td>
                            <td>
                                {{ $rental->user->name }}
                                <div class="small text-muted">{{ $rental->user->email }}</div>
                            </td>
                            <td>{{ $rental->vehicle->displayName() }}</td>
                            <td class="small text-nowrap">
                                @tanggal($rental->start_date) – @tanggal($rental->end_date)
                                @if ($rental->extensions_count > 0)
                                    <span class="badge text-bg-info ms-1" title="Pernah diperpanjang">
                                        <i class="bi bi-calendar-plus"></i> {{ $rental->extensions_count }}×
                                    </span>
                                @endif
                            </td>
                            <td><x-rental-status :status="$rowStatus" /></td>
                            <td class="text-end text-nowrap">@rupiah((float) $rental->total_cost + (float) $rental->extensions_sum_cost)</td>
                            <td class="text-end">
                                <a href="{{ route('admin.rentals.show', $rental) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Tidak ada sewa yang sesuai filter.</td>
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
