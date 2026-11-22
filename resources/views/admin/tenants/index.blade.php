@extends('layouts.app')

@section('title', 'Verifikasi Penyewa')
@section('header', 'Verifikasi Penyewa')

@section('content')
    <ul class="nav nav-tabs mb-0">
        @foreach ($statuses as $tab)
            <li class="nav-item">
                <a @class(['nav-link', 'active' => $status === $tab])
                   href="{{ route('admin.tenants.index', ['status' => $tab->value]) }}">
                    {{ $tab->label() }}
                    <span class="badge rounded-pill text-bg-{{ $tab->badgeClass() }} ms-1">{{ $counts[$tab->value] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card border-0 border-top-0 shadow-sm rounded-top-0">
        @if ($status === \App\Enums\UserStatus::Pending)
            <div class="card-body border-bottom small text-muted py-2">
                <i class="bi bi-info-circle me-1"></i>
                Cocokkan NIK dan nama dengan KTP pendaftar secara verbal sebelum menekan <strong>Terima</strong>.
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th>Email</th>
                        <th>Tanggal Daftar</th>
                        <th>Riwayat</th>
                        @if ($status === \App\Enums\UserStatus::Pending)
                            <th class="text-end">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php $columns = $status === \App\Enums\UserStatus::Pending ? 7 : 6; @endphp
                    @forelse ($tenants as $tenant)
                        <tr>
                            <td class="font-monospace">{{ $tenant->nik }}</td>
                            <td>{{ $tenant->name }}</td>
                            <td>{{ $tenant->phone }}</td>
                            <td>{{ $tenant->email }}</td>
                            <td>@tanggal($tenant->created_at)</td>
                            <td>
                                @if ($tenant->rejection_count > 0)
                                    <span class="badge text-bg-danger d-inline-block mb-1">
                                        Pernah ditolak {{ $tenant->rejection_count }}×
                                    </span>
                                    <div class="small text-muted">terakhir @tanggal($tenant->last_rejected_at)</div>
                                @endif
                                <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="collapse"
                                        data-bs-target="#history-{{ $tenant->id }}" aria-expanded="false">
                                    <i class="bi bi-clock-history me-1"></i>Lihat riwayat ({{ $tenant->verifications->count() }})
                                </button>
                            </td>
                            @if ($status === \App\Enums\UserStatus::Pending)
                                <td class="text-end text-nowrap">
                                    <form method="POST" action="{{ route('admin.tenants.approve', $tenant) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-lg"></i> Terima
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.tenants.reject', $tenant) }}" class="d-inline"
                                          onsubmit="return confirm({{ \Illuminate\Support\Js::from('Tolak pendaftaran '.$tenant->name.'?') }});">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                        {{-- Riwayat pendaftaran & verifikasi (lipat/buka) --}}
                        <tr class="collapse table-light" id="history-{{ $tenant->id }}">
                            <td colspan="{{ $columns }}" class="small">
                                <div class="fw-semibold mb-1"><i class="bi bi-clock-history me-1"></i>Riwayat {{ $tenant->name }}</div>
                                @forelse ($tenant->verifications as $log)
                                    <div class="d-flex flex-wrap gap-2 align-items-center py-1 border-bottom">
                                        <span class="text-muted" style="min-width: 9rem">{{ $log->created_at->translatedFormat('d M Y H:i') }}</span>
                                        <span class="badge text-bg-{{ $log->action->badgeClass() }}">{{ $log->action->label() }}</span>
                                        <span class="text-muted">{{ $log->admin ? 'oleh '.$log->admin->name : 'oleh pendaftar' }}</span>
                                    </div>
                                @empty
                                    <div class="text-muted">Belum ada riwayat tercatat.</div>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columns }}" class="text-center text-muted py-4">
                                Tidak ada penyewa dengan status {{ strtolower($status->label()) }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tenants->hasPages())
            <div class="card-footer bg-white">{{ $tenants->links() }}</div>
        @endif
    </div>
@endsection
