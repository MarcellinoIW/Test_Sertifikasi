@extends('layouts.app')

@section('title', 'Laporan Sewa')
@section('header', 'Laporan Sewa')

@section('content')
    @php
        $months = collect(range(1, 12))->mapWithKeys(
            fn ($m) => [$m => \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F')]
        );
        $isTenant = $type === 'tenant';
        // Laporan per tipe garasi & per penyewa memakai filter periode opsional (dari–sampai).
        $usesPeriodFilter = in_array($type, ['garage_type', 'tenant'], true);
    @endphp

    {{-- Tab jenis laporan --}}
    <ul class="nav nav-tabs mb-0">
        @foreach ($types as $tab => [$tabLabel, $tabIcon])
            <li class="nav-item">
                <a @class(['nav-link', 'active' => $type === $tab])
                   href="{{ route('admin.reports.index', ['type' => $tab]) }}">
                    <i class="bi bi-{{ $tabIcon }} me-1"></i>{{ $tabLabel }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Filter --}}
    <div class="card border-0 border-top-0 shadow-sm rounded-top-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end" novalidate>
                <input type="hidden" name="type" value="{{ $type }}">

                @if ($type === 'daily')
                    <div class="col-sm-4 col-md-3">
                        <label for="month" class="form-label small mb-1">Bulan</label>
                        <select name="month" id="month" class="form-select @error('month') is-invalid @enderror">
                            @foreach ($months as $value => $label)
                                <option value="{{ $value }}" @selected($month === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif

                @if (in_array($type, ['monthly', 'daily'], true))
                    <div class="col-sm-4 col-md-3">
                        <label for="year" class="form-label small mb-1">Tahun</label>
                        <select name="year" id="year" class="form-select @error('year') is-invalid @enderror">
                            @foreach ($years->contains($year) ? $years : $years->push($year)->sortDesc() as $option)
                                <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif

                @if ($usesPeriodFilter)
                    <div class="col-sm-4 col-md-3">
                        <label for="from" class="form-label small mb-1">Dari tanggal <span class="text-muted">(opsional)</span></label>
                        <input type="date" name="from" id="from" value="{{ $from }}" class="form-control @error('from') is-invalid @enderror">
                        @error('from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <label for="to" class="form-label small mb-1">Sampai tanggal <span class="text-muted">(opsional)</span></label>
                        <input type="date" name="to" id="to" value="{{ $to }}" class="form-control @error('to') is-invalid @enderror">
                        @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Tampilkan
                    </button>
                    @if ($usesPeriodFilter && ($from || $to))
                        <a href="{{ route('admin.reports.index', ['type' => $type]) }}" class="btn btn-outline-secondary">Semua periode</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Jumlah Transaksi</div>
                        <div class="fs-3 fw-semibold">{{ number_format($report['total']['transaction_count'], 0, ',', '.') }}</div>
                        <div class="small text-muted">
                            {{ $report['total']['rental_count'] }} sewa · {{ $report['total']['extension_count'] }} perpanjangan
                        </div>
                    </div>
                    <i class="bi bi-receipt text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Pendapatan</div>
                        <div class="fs-4 fw-semibold">@rupiah($report['total']['revenue'])</div>
                        <div class="small text-muted">sewa + listrik + perpanjangan</div>
                    </div>
                    <i class="bi bi-cash-stack text-success"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel laporan --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-bar-chart-line me-1"></i> {{ $report['title'] }}
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ $report['label_heading'] }}</th>
                        <th class="text-end">{{ $isTenant ? 'Jumlah Sewa' : 'Sewa' }}</th>
                        <th class="text-end">Perpanjangan</th>
                        <th class="text-end">Transaksi</th>
                        <th class="text-end">Biaya Sewa</th>
                        <th class="text-end">Biaya Listrik</th>
                        <th class="text-end">Biaya Perpanjangan</th>
                        <th class="text-end pe-3">{{ $isTenant ? 'Total Bayar' : 'Pendapatan' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr @class(['text-muted' => $row['transaction_count'] === 0])>
                            <td class="ps-3">
                                {{ $row['label'] }}
                                @if ($row['sub_label'])
                                    <div class="small text-muted">{{ $row['sub_label'] }}</div>
                                @endif
                            </td>
                            <td class="text-end">{{ $row['rental_count'] }}</td>
                            <td class="text-end">{{ $row['extension_count'] }}</td>
                            <td class="text-end fw-semibold">{{ $row['transaction_count'] }}</td>
                            <td class="text-end">@rupiah($row['rent_cost'])</td>
                            <td class="text-end">@rupiah($row['electricity_cost'])</td>
                            <td class="text-end">@rupiah($row['extension_cost'])</td>
                            <td class="text-end fw-semibold pe-3">@rupiah($row['revenue'])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Tidak ada transaksi pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3">Total</td>
                        <td class="text-end">{{ $report['total']['rental_count'] }}</td>
                        <td class="text-end">{{ $report['total']['extension_count'] }}</td>
                        <td class="text-end">{{ $report['total']['transaction_count'] }}</td>
                        <td class="text-end">@rupiah($report['total']['rent_cost'])</td>
                        <td class="text-end">@rupiah($report['total']['electricity_cost'])</td>
                        <td class="text-end">@rupiah($report['total']['extension_cost'])</td>
                        <td class="text-end pe-3">@rupiah($report['total']['revenue'])</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Satu transaksi = satu sewa baru atau satu perpanjangan. Tanggal acuan: sewa memakai tanggal mulai,
            perpanjangan memakai tanggal perpanjangan dibuat.
        </div>
    </div>
@endsection
