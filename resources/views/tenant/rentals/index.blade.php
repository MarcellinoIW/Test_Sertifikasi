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
            <i class="bi bi-house-check me-1 text-success"></i> Sewa Aktif
            <span class="badge text-bg-success ms-1">{{ $active->count() }}</span>
        </div>
        @include('tenant.rentals._table', ['rentals' => $active, 'emptyText' => 'Tidak ada sewa aktif.'])
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-clock-history me-1 text-secondary"></i> Riwayat Sewa
            <span class="badge text-bg-secondary ms-1">{{ $history->count() }}</span>
        </div>
        @include('tenant.rentals._table', ['rentals' => $history, 'emptyText' => 'Belum ada riwayat sewa.'])
    </div>
@endsection
