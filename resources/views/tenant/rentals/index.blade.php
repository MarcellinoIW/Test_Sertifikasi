@extends('layouts.app')

@section('title', 'Sewa Saya')
@section('header', 'Sewa Saya')

@section('actions')
    <a href="{{ route('tenant.rentals.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Sewa Garasi Baru
    </a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-house-check me-1 text-success"></i> Sewa Aktif &amp; Akan Datang
            <span class="badge text-bg-success ms-1">{{ $current->count() }}</span>
        </div>
        @include('tenant.rentals._table', ['rentals' => $current, 'emptyText' => 'Tidak ada sewa aktif atau yang akan datang.'])
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-clock-history me-1 text-secondary"></i> Riwayat Sewa (Selesai)
            <span class="badge text-bg-secondary ms-1">{{ $history->count() }}</span>
        </div>
        @include('tenant.rentals._table', ['rentals' => $history, 'emptyText' => 'Belum ada riwayat sewa.'])
    </div>
@endsection
