@extends('layouts.app')

@section('title', 'Detail Sewa '.$rental->garage->code)
@section('header', 'Detail Sewa Garasi '.$rental->garage->code)

@section('actions')
    @if ($status === 'active')
        <a href="{{ route('admin.extensions.create', $rental) }}" class="btn btn-primary">
            <i class="bi bi-calendar-plus me-1"></i> Perpanjang
        </a>
    @endif
    <a href="{{ route('admin.rentals.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Data Sewa
    </a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Informasi sewa --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-info-circle me-1"></i> Informasi Sewa</span>
                    <x-rental-status :status="$status" />
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-normal text-muted">Penyewa</dt>
                        <dd class="col-sm-8">
                            {{ $rental->user->name }}
                            <div class="small text-muted">{{ $rental->user->email }} · {{ $rental->user->phone }} · NIK {{ $rental->user->nik }}</div>
                        </dd>
                        <dt class="col-sm-4 fw-normal text-muted">Garasi</dt>
                        <dd class="col-sm-8">{{ $rental->garage->code }} · {{ $rental->garage->type->label() }}</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Kendaraan</dt>
                        <dd class="col-sm-8">{{ $rental->vehicle->displayName() }}</dd>
                        @if ($rental->vehicle->type->isElectric())
                            <dt class="col-sm-4 fw-normal text-muted">Baterai</dt>
                            <dd class="col-sm-8">
                                Sisa {{ (float) $rental->battery_remaining_kwh }} kWh dari {{ (float) $rental->vehicle->battery_capacity_kwh }} kWh
                            </dd>
                        @endif
                        <dt class="col-sm-4 fw-normal text-muted">Jenis Sewa</dt>
                        <dd class="col-sm-8">{{ $rental->rental_type->label() }} · {{ $rental->days }} hari</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Periode Awal</dt>
                        <dd class="col-sm-8">@tanggal($rental->start_date) – @tanggal($rental->start_date->copy()->addDays($rental->days))</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Tanggal Selesai</dt>
                        <dd class="col-sm-8 fw-semibold">@tanggal($rental->end_date)</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Dipesan pada</dt>
                        <dd class="col-sm-8 mb-0">{{ $rental->created_at->translatedFormat('d M Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Riwayat perpanjangan + bukti bayar --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-calendar-plus me-1"></i> Riwayat Perpanjangan</div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Selesai Lama → Baru</th>
                                <th>Rincian</th>
                                <th>Admin</th>
                                <th class="text-end">Biaya</th>
                                <th class="text-center">Bukti Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rental->extensions as $extension)
                                <tr>
                                    <td class="text-nowrap">@tanggal($extension->created_at)</td>
                                    <td class="text-nowrap">@tanggal($extension->old_end_date) → @tanggal($extension->new_end_date)</td>
                                    <td class="small">
                                        +{{ $extension->additional_days }} hari:
                                        {{ $extension->monthly_blocks }} bulan + {{ $extension->daily_days }} hari harian
                                    </td>
                                    <td class="small">{{ $extension->admin->name }}</td>
                                    <td class="text-end text-nowrap">@rupiah($extension->cost)</td>
                                    <td class="text-center">
                                        <x-payment-proof :model="$extension" label="Bukti bayar perpanjangan" size="sm" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Belum pernah diperpanjang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Rincian biaya --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-receipt me-1"></i> Rincian Biaya</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7 fw-normal">Sewa ({{ $rental->rental_type->label() }})</dt>
                        <dd class="col-5 text-end">@rupiah($rental->rent_cost)</dd>
                        <dt class="col-7 fw-normal">Isi listrik</dt>
                        <dd class="col-5 text-end">@rupiah($rental->electricity_cost)</dd>
                        @if ($rental->charged_kwh !== null)
                            <dd class="col-12 small text-muted">
                                {{ (float) $rental->charged_kwh }} kWh × @rupiah($rental->electricity_price_per_kwh)
                            </dd>
                        @endif
                        <dt class="col-7 fw-normal">Perpanjangan ({{ $rental->extensions->count() }}×)</dt>
                        <dd class="col-5 text-end">@rupiah($rental->extensions->sum('cost'))</dd>
                    </dl>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">Total Dibayar</span>
                        <span class="fw-bold text-primary">@rupiah($rental->grandTotal())</span>
                    </div>
                </div>
            </div>

            {{-- Bukti pelunasan penyewa --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-image me-1"></i> Bukti Pelunasan Penyewa</div>
                <div class="card-body">
                    <x-payment-proof :model="$rental" label="Bukti pelunasan" />
                    <div class="small text-muted text-center mt-2">
                        Diunggah @tanggal($rental->created_at) · @rupiah($rental->total_cost)
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
