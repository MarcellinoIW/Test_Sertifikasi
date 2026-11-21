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
                        @if ($status === \App\Enums\UserStatus::Pending)
                            <th class="text-end">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenants as $tenant)
                        <tr>
                            <td class="font-monospace">{{ $tenant->nik }}</td>
                            <td>{{ $tenant->name }}</td>
                            <td>{{ $tenant->phone }}</td>
                            <td>{{ $tenant->email }}</td>
                            <td>@tanggal($tenant->created_at)</td>
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
                                          onsubmit="return confirm('Tolak pendaftaran {{ e($tenant->name) }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg"></i> Tolak
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
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
