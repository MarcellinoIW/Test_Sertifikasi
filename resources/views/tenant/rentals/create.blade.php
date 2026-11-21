@extends('layouts.app')

@section('title', 'Sewa Garasi Baru')
@section('header', 'Sewa Garasi Baru')

@section('content')
    @php
        $vehicleOptions = collect($vehicleTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all();
        $rentalOptions = collect($rentalTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all();
        $garageLabels = collect($garageTypes)->mapWithKeys(fn ($t) => [$t->value => "{$t->prefix()} · {$t->label()}"])->all();
    @endphp

    <form method="POST" action="{{ route('tenant.rentals.store') }}" enctype="multipart/form-data" id="rental-form" novalidate>
        @csrf

        <div class="row g-3">
            <div class="col-lg-8">
                {{-- 1. Detail kendaraan --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">
                        <span class="badge text-bg-primary me-1">1</span> Detail Kendaraan
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <x-form.select name="vehicle_type" label="Tipe Kendaraan" :options="$vehicleOptions"
                                               placeholder="-- Pilih tipe kendaraan --" required />
                            </div>
                            <div class="col-md-6" id="plate-field">
                                <x-form.input name="plate_number" label="Nomor Plat" maxlength="15" required
                                              placeholder="B 1234 CD" style="text-transform: uppercase" />
                            </div>
                        </div>

                        <x-form.input name="brand_model" label="Merek & Tipe" maxlength="100" required
                                      placeholder="Contoh: Toyota Avanza / Honda Beat" />

                        <div id="electric-section" class="row" hidden>
                            <div class="col-md-6">
                                <x-form.input name="battery_capacity" label="Kapasitas Baterai" :value="$batteryCapacity.' kWh'"
                                              readonly disabled help="Ditetapkan sistem." />
                            </div>
                            <div class="col-md-6">
                                <x-form.input name="battery_remaining_kwh" label="Sisa Baterai (kWh)" type="number"
                                              min="0" :max="$batteryCapacity" step="0.1" required
                                              help="Angka 0 – {{ $batteryCapacity }}. Dipakai menghitung biaya isi listrik di garasi E." />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Detail sewa --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">
                        <span class="badge text-bg-primary me-1">2</span> Detail Sewa
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <x-form.select name="rental_type" label="Tipe Sewa" :options="$rentalOptions" value="daily" required />
                            </div>
                            <div class="col-md-6">
                                <x-form.input name="days" label="Jumlah Hari" type="number" min="1" :max="$maxDailyDays" value="1" required
                                              help="Harian: 1 – {{ $maxDailyDays }} hari. Bulanan: {{ $monthlyDays }} hari (otomatis)." />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <x-form.input name="start_date_display" label="Tanggal Mulai" :value="\App\Support\Formatter::date($today)"
                                              readonly disabled help="Sewa selalu dimulai hari ini." />
                            </div>
                            <div class="col-md-6">
                                <x-form.input name="end_date_display" label="Tanggal Selesai" readonly disabled
                                              help="Garasi dapat dipakai sampai sebelum tanggal ini." />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Pilih garasi --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">
                        <span class="badge text-bg-primary me-1">3</span> Pilih Garasi
                    </div>
                    <div class="card-body">
                        <x-form.select name="garage_type" label="Tipe Garasi" :options="$garageLabels"
                                       placeholder="-- Pilih tipe kendaraan dulu --" required />

                        <label class="form-label">Garasi Tersedia Hari Ini <span class="text-danger">*</span></label>
                        <div id="garage-list" class="row g-2">
                            <div class="col-12 text-muted small">Pilih tipe kendaraan dan tipe garasi untuk melihat garasi yang tersedia.</div>
                        </div>
                        @error('garage_id')
                            <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- 4. Bukti pelunasan --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">
                        <span class="badge text-bg-primary me-1">4</span> Bukti Pelunasan
                    </div>
                    <div class="card-body">
                        <x-form.input name="payment_proof" label="Unggah Bukti Pembayaran" type="file"
                                      accept=".jpg,.jpeg,.png,image/jpeg,image/png" required
                                      help="Format JPG/PNG, maksimal 2 MB. Bayar sesuai total pada ringkasan biaya." />
                        @if ($errors->any())
                            <div class="form-text text-warning"><i class="bi bi-info-circle me-1"></i>Silakan pilih ulang file bukti pembayaran.</div>
                        @endif
                        <img id="proof-preview" class="img-thumbnail mt-2" style="max-height: 180px" alt="Pratinjau bukti" hidden>
                    </div>
                </div>
            </div>

            {{-- Ringkasan biaya (pratinjau) --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm position-sticky" style="top: 1rem">
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-receipt me-1"></i> Ringkasan Biaya</div>
                    <div class="card-body">
                        <dl class="row small mb-0">
                            <dt class="col-6 fw-normal text-muted">Garasi</dt>
                            <dd class="col-6 text-end" id="sum-garage">–</dd>
                            <dt class="col-6 fw-normal text-muted">Harga harian</dt>
                            <dd class="col-6 text-end" id="sum-daily">–</dd>
                            <dt class="col-6 fw-normal text-muted">Periode</dt>
                            <dd class="col-6 text-end" id="sum-period">–</dd>
                        </dl>
                        <hr>
                        <dl class="row mb-0">
                            <dt class="col-6 fw-normal">Biaya sewa</dt>
                            <dd class="col-6 text-end" id="sum-rent">Rp 0</dd>
                            <dt class="col-6 fw-normal">Biaya listrik</dt>
                            <dd class="col-6 text-end" id="sum-electricity">Rp 0</dd>
                            <dd class="col-12 small text-muted mb-1" id="sum-electricity-note"></dd>
                        </dl>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Total</span>
                            <span class="fs-4 fw-bold text-primary" id="sum-total">Rp 0</span>
                        </div>
                        <p class="small text-muted mt-2 mb-3">
                            Pratinjau. Biaya final dihitung ulang oleh sistem saat disimpan.
                        </p>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Sewa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Hanya untuk pratinjau & interaksi form. Sumber kebenaran biaya: RentalPriceCalculator (server).
        (() => {
            const cfg = {
                compatibility: @json($compatibility),
                garageLabels: @json($garageLabels),
                maxDailyDays: {{ $maxDailyDays }},
                monthlyDays: {{ $monthlyDays }},
                multiplier: {{ $monthlyMultiplier }},
                capacity: {{ $batteryCapacity }},
                today: @json($today->toDateString()),
                endpoint: @json(route('tenant.rentals.available-garages')),
                oldGarageId: @json(old('garage_id')),
                oldGarageType: @json(old('garage_type')),
            };

            const $ = (id) => document.getElementById(id);
            const els = {
                vehicle: $('vehicle_type'), plateField: $('plate-field'), plate: $('plate_number'),
                electric: $('electric-section'), battery: $('battery_remaining_kwh'),
                rentalType: $('rental_type'), days: $('days'), endDate: $('end_date_display'),
                garageType: $('garage_type'), garageList: $('garage-list'),
                proof: $('payment_proof'), proofPreview: $('proof-preview'),
            };
            const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
            const fmtDate = (d) => new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
            const [ty, tm, td] = cfg.today.split('-').map(Number);

            let garages = [];
            let fetchToken = 0;

            const vehicleInfo = () => cfg.compatibility[els.vehicle.value] ?? null;
            const selectedGarage = () => {
                const checked = document.querySelector('input[name="garage_id"]:checked');
                return checked ? garages.find((g) => String(g.id) === checked.value) : null;
            };

            function onVehicleChange() {
                const info = vehicleInfo();
                const requiresPlate = info ? info.requiresPlate : true;
                els.plateField.hidden = !requiresPlate;
                els.plate.disabled = !requiresPlate;

                const isElectric = info ? info.isElectric : false;
                els.electric.hidden = !isElectric;
                els.battery.disabled = !isElectric;

                // Batasi pilihan tipe garasi sesuai kompatibilitas (VehicleType::allowedGarageTypes).
                const allowed = info ? info.garageTypes : [];
                const current = els.garageType.value || cfg.oldGarageType;
                els.garageType.innerHTML = '';
                els.garageType.add(new Option(info ? '-- Pilih tipe garasi --' : '-- Pilih tipe kendaraan dulu --', ''));
                allowed.forEach((type) => els.garageType.add(new Option(cfg.garageLabels[type], type)));
                els.garageType.value = allowed.includes(current) ? current : (allowed.length === 1 ? allowed[0] : '');
                cfg.oldGarageType = null;

                loadGarages();
            }

            function onRentalTypeChange() {
                if (els.rentalType.value === 'monthly') {
                    els.days.max = cfg.monthlyDays;
                    els.days.value = cfg.monthlyDays;
                    els.days.readOnly = true;
                } else {
                    els.days.max = cfg.maxDailyDays;
                    els.days.readOnly = false;
                    if (Number(els.days.value) > cfg.maxDailyDays) els.days.value = cfg.maxDailyDays;
                }
                refreshSummary();
            }

            async function loadGarages() {
                const vehicle = els.vehicle.value;
                const garageType = els.garageType.value;
                garages = [];

                if (!vehicle || !garageType) {
                    els.garageList.innerHTML = '<div class="col-12 text-muted small">Pilih tipe kendaraan dan tipe garasi untuk melihat garasi yang tersedia.</div>';
                    refreshSummary();
                    return;
                }

                const token = ++fetchToken;
                els.garageList.innerHTML = '<div class="col-12 text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Memuat garasi…</div>';

                try {
                    const url = `${cfg.endpoint}?${new URLSearchParams({ vehicle_type: vehicle, garage_type: garageType })}`;
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    if (!res.ok) throw new Error(res.status);
                    const json = await res.json();
                    if (token !== fetchToken) return;
                    garages = json.data;
                    renderGarages();
                } catch (e) {
                    if (token === fetchToken) {
                        els.garageList.innerHTML = '<div class="col-12 text-danger small">Gagal memuat daftar garasi. Muat ulang halaman.</div>';
                    }
                }
                refreshSummary();
            }

            function renderGarages() {
                if (garages.length === 0) {
                    els.garageList.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0 small">Tidak ada garasi tipe ini yang tersedia hari ini.</div></div>';
                    return;
                }

                els.garageList.innerHTML = garages.map((g) => `
                    <div class="col-6 col-md-4 col-xl-3">
                        <input type="radio" class="btn-check" name="garage_id" id="garage-${g.id}" value="${g.id}"
                               ${String(g.id) === String(cfg.oldGarageId) ? 'checked' : ''}>
                        <label class="btn btn-outline-primary w-100 text-start py-2" for="garage-${g.id}">
                            <div class="fw-bold fs-5">${g.code}</div>
                            <div class="small">${rupiah(g.daily_price)}/hari</div>
                            <div class="small opacity-75">${rupiah(g.monthly_price)}/bulan</div>
                            ${g.electricity_price_per_kwh !== null ? `<div class="small opacity-75">${rupiah(g.electricity_price_per_kwh)}/kWh</div>` : ''}
                        </label>
                    </div>`).join('');
                cfg.oldGarageId = null;
            }

            function refreshSummary() {
                const isMonthly = els.rentalType.value === 'monthly';
                const days = isMonthly ? cfg.monthlyDays : Math.max(0, parseInt(els.days.value, 10) || 0);
                const validDays = days >= 1 && (isMonthly || days <= cfg.maxDailyDays);

                els.endDate.value = validDays ? fmtDate(new Date(ty, tm - 1, td + days)) : '';
                $('sum-period').textContent = validDays ? `${days} hari (${isMonthly ? 'bulanan' : 'harian'})` : '–';

                const garage = selectedGarage();
                const info = vehicleInfo();
                $('sum-garage').textContent = garage ? garage.code : '–';
                $('sum-daily').textContent = garage ? rupiah(garage.daily_price) : '–';

                const rent = garage && validDays ? (isMonthly ? cfg.multiplier * garage.daily_price : days * garage.daily_price) : 0;

                let electricity = 0;
                let note = '';
                if (info && info.isElectric && garage) {
                    if (garage.type === 'electric') {
                        const remaining = parseFloat(els.battery.value);
                        if (!Number.isNaN(remaining) && remaining >= 0 && remaining <= cfg.capacity) {
                            const kwh = cfg.capacity - remaining;
                            electricity = kwh * garage.electricity_price_per_kwh;
                            note = `${kwh.toLocaleString('id-ID')} kWh × ${rupiah(garage.electricity_price_per_kwh)}`;
                        } else {
                            note = 'Isi sisa baterai untuk menghitung biaya listrik.';
                        }
                    } else {
                        note = 'Garasi mobil (C) tidak menyediakan pengisian listrik.';
                    }
                }

                $('sum-rent').textContent = rupiah(rent);
                $('sum-electricity').textContent = rupiah(electricity);
                $('sum-electricity-note').textContent = note;
                $('sum-total').textContent = rupiah(rent + electricity);
            }

            els.vehicle.addEventListener('change', onVehicleChange);
            els.garageType.addEventListener('change', loadGarages);
            els.rentalType.addEventListener('change', onRentalTypeChange);
            els.days.addEventListener('input', refreshSummary);
            els.battery.addEventListener('input', refreshSummary);
            els.garageList.addEventListener('change', refreshSummary);
            els.proof.addEventListener('change', () => {
                const file = els.proof.files[0];
                els.proofPreview.hidden = !file;
                if (file) els.proofPreview.src = URL.createObjectURL(file);
            });

            onVehicleChange();
            onRentalTypeChange();
        })();
    </script>
@endpush
