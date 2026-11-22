{{--
    Badge status sewa.
    Pemakaian: <x-rental-status :status="$rental->statusOn($today)" />
    Status: upcoming (Akan Datang), active (Aktif), finished (Selesai).
--}}
@props(['status'])

@php
    $color = match ($status) {
        'upcoming' => 'info',
        'active' => 'success',
        default => 'secondary',
    };
@endphp

<span class="badge text-bg-{{ $color }}">{{ \App\Models\Rental::STATUSES[$status] ?? $status }}</span>
