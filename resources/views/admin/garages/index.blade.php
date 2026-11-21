@extends('layouts.app')

@section('title', 'Daftar Garasi')
@section('header', 'Daftar Garasi')

@section('actions')
    <a href="{{ route('admin.garages.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Garasi
    </a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.garages.index') }}" class="row g-2 align-items-end">
                <div class="col-sm-4 col-md-3">
                    <label for="type" class="form-label small mb-1">Tipe</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Semua tipe</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(request('type') === $type->value)>
                                {{ $type->prefix() }} · {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 col-md-3">
                    <label for="status" class="form-label small mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.garages.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-house-door me-1"></i> Garasi</span>
            <span class="small text-muted">{{ $garages->total() }} garasi</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Tipe</th>
                        <th class="text-end">Harga Harian</th>
                        <th class="text-end">Harga / kWh</th>
                        <th>Status</th>
                        <th>Penyewa Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($garages as $garage)
                        @php $rental = $garage->rentals->first(); @endphp
                        <tr>
                            <td><span class="badge text-bg-primary fs-6">{{ $garage->code }}</span></td>
                            <td>{{ $garage->type->label() }}</td>
                            <td class="text-end">@rupiah($garage->daily_price)</td>
                            <td class="text-end">
                                @if ($garage->electricity_price_per_kwh !== null)
                                    @rupiah($garage->electricity_price_per_kwh)
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $garage->status->badgeClass() }}">{{ $garage->status->label() }}</span>
                            </td>
                            <td>
                                @if ($rental)
                                    {{ $rental->user->name }}
                                    <div class="small text-muted">
                                        {{ $rental->vehicle->displayName() }} · s.d. @tanggal($rental->end_date)
                                    </div>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Tidak ada garasi yang sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($garages->hasPages())
            <div class="card-footer bg-white">{{ $garages->links() }}</div>
        @endif
    </div>
@endsection
