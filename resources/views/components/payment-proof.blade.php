{{--
    Menampilkan gambar bukti bayar (Rental atau RentalExtension).
    Klik gambar untuk membuka ukuran penuh di tab baru.

    Contoh:
      <x-payment-proof :model="$rental" label="Bukti pelunasan" />            (ukuran besar, di kartu)
      <x-payment-proof :model="$extension" label="Bukti bayar" size="sm" />   (ukuran kecil, di tabel)
--}}
@props([
    'model',
    'label' => 'Bukti bayar',
    'size' => 'lg',
])

@php
    $maxHeight = $size === 'sm' ? '56px' : '260px';
@endphp

@if ($model->hasPaymentProofFile())
    <div {{ $attributes->class(['payment-proof', 'text-center' => $size !== 'sm']) }}>
        <a href="{{ $model->paymentProofUrl() }}" target="_blank" rel="noopener"
           title="Buka {{ strtolower($label) }} ukuran penuh">
            <img src="{{ $model->paymentProofUrl() }}" alt="{{ $label }}"
                 class="img-thumbnail" style="max-height: {{ $maxHeight }}; object-fit: contain;" loading="lazy">
        </a>
        @if ($size !== 'sm')
            <div class="mt-2">
                <a href="{{ $model->paymentProofUrl() }}" target="_blank" rel="noopener" class="small">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka ukuran penuh
                </a>
            </div>
        @endif
    </div>
@else
    <span {{ $attributes->class(['small text-danger']) }}>
        <i class="bi bi-exclamation-triangle me-1"></i>File {{ strtolower($label) }} tidak ditemukan
    </span>
@endif
