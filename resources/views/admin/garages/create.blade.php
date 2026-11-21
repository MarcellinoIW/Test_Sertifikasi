@extends('layouts.app')

@section('title', 'Tambah Garasi')
@section('header', 'Tambah Garasi (Batch)')

@section('actions')
    <a href="{{ route('admin.garages.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@section('content')
    @php
        $typeOptions = collect($types)->mapWithKeys(fn ($t) => [$t->value => "{$t->prefix()} · {$t->label()}"])->all();
        $prefixes = collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->prefix()])->all();
    @endphp

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.garages.store') }}" novalidate>
                        @csrf

                        <x-form.select name="type" label="Tipe Garasi" :options="$typeOptions"
                                       placeholder="-- Pilih tipe garasi --" required />

                        <x-form.input name="quantity" label="Jumlah Garasi" type="number" min="1" :max="$maxNumber"
                                      value="1" required help="Antara 1 sampai {{ $maxNumber }}. Kode dibuat otomatis." />

                        <x-form.input name="daily_price" label="Harga Sewa Harian" type="number" min="1" step="1"
                                      prefix="Rp" required help="Harga bulanan = {{ config('garasi.monthly_multiplier') }} × harga harian." />

                        <div id="electricity-field" hidden>
                            <x-form.input name="electricity_price_per_kwh" label="Harga Isi Listrik per kWh" type="number"
                                          min="1" step="1" prefix="Rp" required help="Wajib untuk garasi mobil listrik." />
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Garasi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-eye me-1"></i> Pratinjau Kode</div>
                <div class="card-body">
                    <p id="code-preview" class="fs-5 mb-2 text-muted">Pilih tipe garasi terlebih dahulu.</p>
                    <ul class="list-unstyled small text-muted mb-0">
                        @foreach ($types as $type)
                            <li>
                                {{ $type->label() }}: kode terakhir
                                <strong>{{ $lastNumbers[$type->value] ? $type->prefix().str_pad($lastNumbers[$type->value], 3, '0', STR_PAD_LEFT) : 'belum ada' }}</strong>
                                (sisa kuota {{ $maxNumber - $lastNumbers[$type->value] }})
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Pratinjau saja; kode sebenarnya dibuat di server (GarageCodeGenerator).
        (() => {
            const lastNumbers = @json($lastNumbers);
            const prefixes = @json($prefixes);
            const maxNumber = {{ $maxNumber }};
            const typeSelect = document.getElementById('type');
            const qtyInput = document.getElementById('quantity');
            const electricField = document.getElementById('electricity-field');
            const electricInput = document.getElementById('electricity_price_per_kwh');
            const preview = document.getElementById('code-preview');

            const pad = (n) => String(n).padStart(3, '0');

            function refresh() {
                const type = typeSelect.value;
                const isElectric = type === 'electric';
                electricField.hidden = !isElectric;
                electricInput.disabled = !isElectric;

                if (!type) {
                    preview.className = 'fs-5 mb-2 text-muted';
                    preview.textContent = 'Pilih tipe garasi terlebih dahulu.';
                    return;
                }

                const qty = parseInt(qtyInput.value, 10) || 0;
                const first = lastNumbers[type] + 1;
                const last = lastNumbers[type] + qty;

                if (qty < 1) {
                    preview.className = 'fs-5 mb-2 text-muted';
                    preview.textContent = 'Isi jumlah garasi.';
                } else if (last > maxNumber) {
                    preview.className = 'fs-6 mb-2 text-danger';
                    preview.textContent = `Melebihi batas ${prefixes[type]}${maxNumber}. Sisa kuota ${maxNumber - lastNumbers[type]} garasi.`;
                } else {
                    preview.className = 'fs-5 mb-2 text-primary fw-semibold';
                    preview.textContent = qty === 1
                        ? `${prefixes[type]}${pad(first)}`
                        : `${prefixes[type]}${pad(first)} – ${prefixes[type]}${pad(last)} (${qty} garasi)`;
                }
            }

            typeSelect.addEventListener('change', refresh);
            qtyInput.addEventListener('input', refresh);
            refresh();
        })();
    </script>
@endpush
