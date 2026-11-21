@php
    $flashes = [
        'success' => ['success', 'check-circle-fill'],
        'error' => ['danger', 'exclamation-triangle-fill'],
        'warning' => ['warning', 'exclamation-circle-fill'],
        'info' => ['info', 'info-circle-fill'],
    ];
@endphp

@foreach ($flashes as $key => [$color, $icon])
    @if (session($key))
        <div class="alert alert-{{ $color }} alert-dismissible fade show d-flex align-items-start" role="alert">
            <i class="bi bi-{{ $icon }} me-2 mt-1"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-start" role="alert">
        <i class="bi bi-x-octagon-fill me-2 mt-1"></i>
        <div>Data belum valid. Periksa kembali isian yang ditandai merah.</div>
    </div>
@endif
