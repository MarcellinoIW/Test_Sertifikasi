@extends('layouts.app')

@section('title', 'Detail Sewa '.$rental->garage->code)
@section('header', 'Detail Sewa Garasi '.$rental->garage->code)

@section('actions')
    <a href="{{ route('tenant.rentals.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Sewa Saya
    </a>
@endsection

@section('content')
    @php $status = $rental->statusOn(today()); @endphp

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-info-circle me-1"></i> Informasi Sewa</span>
                    <x-rental-status :status="$status" />
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
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
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-calendar-plus me-1"></i> Riwayat Perpanjangan</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Selesai Lama → Baru</th>
                                <th>Rincian</th>
                                <th class="text-end">Biaya</th>
                                <th class="text-center">Bukti Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rental->extensions as $extension)
                                <tr>
                                    <td>@tanggal($extension->created_at)</td>
                                    <td>@tanggal($extension->old_end_date) → @tanggal($extension->new_end_date)</td>
                                    <td class="small">
                                        +{{ $extension->additional_days }} hari:
                                        {{ $extension->monthly_blocks }} bulan + {{ $extension->daily_days }} hari harian
                                    </td>
                                    <td class="text-end">@rupiah($extension->cost)</td>
                                    <td class="text-center">
                                        <x-payment-proof :model="$extension" label="Bukti bayar perpanjangan" size="sm" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Belum pernah diperpanjang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-receipt me-1"></i> Rincian Biaya</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-7 fw-normal">Sewa ({{ $rental->rental_type->label() }})</dt>
                        <dd class="col-5 text-end">@rupiah($rental->rent_cost)</dd>
                        <dd class="col-12 small text-muted">
                            @if ($rental->rental_type === \App\Enums\RentalType::Monthly)
                                {{ config('garasi.monthly_multiplier') }} × @rupiah($rental->daily_price)
                            @else
                                {{ $rental->days }} hari × @rupiah($rental->daily_price)
                            @endif
                        </dd>
                        <dt class="col-7 fw-normal">Isi listrik</dt>
                        <dd class="col-5 text-end">@rupiah($rental->electricity_cost)</dd>
                        @if ($rental->charged_kwh !== null)
                            <dd class="col-12 small text-muted">
                                {{ (float) $rental->charged_kwh }} kWh × @rupiah($rental->electricity_price_per_kwh)
                            </dd>
                        @endif
                        @if ($rental->extensions->isNotEmpty())
                            <dt class="col-7 fw-normal">Perpanjangan</dt>
                            <dd class="col-5 text-end">@rupiah($rental->extensions->sum('cost'))</dd>
                        @endif
                    </dl>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">Total Dibayar</span>
                        <span class="fw-bold text-primary">@rupiah($rental->grandTotal())</span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-image me-1"></i> Bukti Pelunasan</div>
                <div class="card-body">
                    <x-payment-proof :model="$rental" label="Bukti pelunasan" />
                </div>
            </div>
        </div>
    </div>
@endsection
