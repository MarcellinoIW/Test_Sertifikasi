@extends('layouts.app')

@section('title', 'Perpanjang Sewa '.$rental->garage->code)
@section('header', 'Perpanjang Sewa Garasi '.$rental->garage->code)

@section('actions')
    <a href="{{ route('admin.extensions.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Informasi sewa --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-1"></i> Informasi Sewa</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-normal text-muted">Garasi</dt>
                        <dd class="col-sm-8">{{ $rental->garage->code }} · {{ $rental->garage->type->label() }}</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Penyewa</dt>
                        <dd class="col-sm-8">{{ $rental->user->name }} <span class="text-muted">({{ $rental->user->phone }})</span></dd>
                        <dt class="col-sm-4 fw-normal text-muted">Kendaraan</dt>
                        <dd class="col-sm-8">{{ $rental->vehicle->displayName() }}</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Tanggal Mulai</dt>
                        <dd class="col-sm-8">@tanggal($rental->start_date)</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Tanggal Selesai Saat Ini</dt>
                        <dd class="col-sm-8 fw-semibold">@tanggal($rental->end_date)</dd>
                        <dt class="col-sm-4 fw-normal text-muted">Harga Harian (snapshot)</dt>
                        <dd class="col-sm-8 mb-0">
                            @rupiah($rental->daily_price)
                            <span class="text-muted small">· 1 bulan = {{ $monthlyMultiplier }} × harian = @rupiah($monthlyPrice)</span>
                        </dd>
                        <dt class="col-sm-4 fw-normal text-muted mt-2">Bukti Pelunasan Penyewa</dt>
                        <dd class="col-sm-8 mb-0 mt-2">
                            <x-payment-proof :model="$rental" label="Bukti pelunasan" size="sm" />
                            <a href="{{ route('admin.rentals.show', $rental) }}" class="small ms-2">Lihat detail sewa</a>
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Form perpanjangan --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-calendar-plus me-1"></i> Data Perpanjangan</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.extensions.store', $rental) }}" enctype="multipart/form-data"
                          id="extension-form" novalidate>
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <x-form.input name="additional_days" label="Tambahan Hari" type="number" min="1" step="1"
                                              help="Minimal 1 hari, tanpa batas maksimal." />
                            </div>
                            <div class="col-md-6">
                                <x-form.input name="new_end_date" label="atau Tanggal Selesai Baru" type="date"
                                              :min="$rental->end_date->copy()->addDay()->toDateString()"
                                              help="Harus setelah {{ $rental->end_date->translatedFormat('d M Y') }}." />
                            </div>
                        </div>

                        <x-form.input name="payment_proof" label="Bukti Bayar Perpanjangan" type="file"
                                      accept=".jpg,.jpeg,.png,image/jpeg,image/png" required
                                      help="Format JPG/PNG, maks. {{ str_replace('.', ',', config('garasi.max_upload_kb') / 1024) }}MB" />
                        @if ($errors->any())
                            <div class="form-text text-warning"><i class="bi bi-info-circle me-1"></i>Silakan pilih ulang file bukti bayar.</div>
                        @endif

                        <p class="small text-muted mt-3 mb-3">
                            <i class="bi bi-lightning-charge me-1"></i>Perpanjangan tidak dikenai biaya isi listrik.
                        </p>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Perpanjangan
                        </button>
                    </form>
                </div>
            </div>

            {{-- Riwayat perpanjangan --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i> Riwayat Perpanjangan</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Selesai Lama → Baru</th>
                                <th>Rincian</th>
                                <th>Admin</th>
                                <th class="text-end">Biaya</th>
                                <th class="text-center">Bukti Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rental->extensions as $extension)
                                <tr>
                                    <td>@tanggal($extension->created_at)</td>
                                    <td>@tanggal($extension->old_end_date) → @tanggal($extension->new_end_date)</td>
                                    <td class="small">
                                        +{{ $extension->additional_days }} hari:
                                        {{ $extension->monthly_blocks }} bulan + {{ $extension->daily_days }} hari harian
                                    </td>
                                    <td class="small">{{ $extension->admin->name }}</td>
                                    <td class="text-end">@rupiah($extension->cost)</td>
                                    <td class="text-center">
                                        <x-payment-proof :model="$extension" label="Bukti bayar perpanjangan" size="sm" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Belum pernah diperpanjang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Rincian biaya (dari server) --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm position-sticky" style="top: 1rem">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-receipt me-1"></i> Rincian Biaya Perpanjangan</div>
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-6 fw-normal text-muted">Selesai lama</dt>
                        <dd class="col-6 text-end">@tanggal($rental->end_date)</dd>
                        <dt class="col-6 fw-normal text-muted">Selesai baru</dt>
                        <dd class="col-6 text-end fw-semibold" id="bd-new-end">–</dd>
                        <dt class="col-6 fw-normal text-muted">Tambahan</dt>
                        <dd class="col-6 text-end" id="bd-days">–</dd>
                    </dl>
                    <hr>
                    <dl class="row mb-0">
                        <dt class="col-7 fw-normal" id="bd-monthly-label">Blok bulan</dt>
                        <dd class="col-5 text-end" id="bd-monthly">Rp 0</dd>
                        <dt class="col-7 fw-normal" id="bd-daily-label">Hari harian</dt>
                        <dd class="col-5 text-end" id="bd-daily">Rp 0</dd>
                    </dl>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Total</span>
                        <span class="fs-4 fw-bold text-primary" id="bd-total">Rp 0</span>
                    </div>
                    <p class="small mb-0 mt-2" id="bd-status">
                        <span class="text-muted">Isi tambahan hari atau tanggal selesai baru.</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Hanya sinkronisasi input & tampilan. Rincian biaya diambil dari server
        // (RentalPriceCalculator::extensionCost) lewat endpoint pratinjau.
        (() => {
            const cfg = {
                oldEnd: @json($rental->end_date->toDateString()),
                previewUrl: @json(route('admin.extensions.preview', $rental)),
                monthlyMultiplier: {{ $monthlyMultiplier }},
                dailyPrice: {{ (float) $rental->daily_price }},
            };

            const $ = (id) => document.getElementById(id);
            const daysInput = $('additional_days');
            const dateInput = $('new_end_date');
            const rupiah = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

            const [oy, om, od] = cfg.oldEnd.split('-').map(Number);
            const oldEnd = Date.UTC(oy, om - 1, od);
            const DAY = 86400000;

            const toIso = (ms) => new Date(ms).toISOString().slice(0, 10);
            const parseIso = (s) => {
                const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
                return m ? Date.UTC(+m[1], +m[2] - 1, +m[3]) : null;
            };

            let timer = null;
            let token = 0;

            function reset(message, isError = false) {
                $('bd-new-end').textContent = '–';
                $('bd-days').textContent = '–';
                $('bd-monthly-label').textContent = 'Blok bulan';
                $('bd-daily-label').textContent = 'Hari harian';
                $('bd-monthly').textContent = 'Rp 0';
                $('bd-daily').textContent = 'Rp 0';
                $('bd-total').textContent = 'Rp 0';
                $('bd-status').innerHTML = `<span class="${isError ? 'text-danger' : 'text-muted'}">${message}</span>`;
            }

            function schedulePreview() {
                clearTimeout(timer);
                const days = parseInt(daysInput.value, 10);
                if (!Number.isInteger(days) || days < 1) {
                    reset(daysInput.value === '' && dateInput.value === ''
                        ? 'Isi tambahan hari atau tanggal selesai baru.'
                        : 'Tanggal selesai baru harus setelah tanggal selesai lama.', daysInput.value !== '' || dateInput.value !== '');
                    return;
                }
                $('bd-status').innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Menghitung…</span>';
                timer = setTimeout(() => loadPreview(days), 250);
            }

            async function loadPreview(days) {
                const current = ++token;
                try {
                    const res = await fetch(`${cfg.previewUrl}?additional_days=${days}`, { headers: { Accept: 'application/json' } });
                    const json = await res.json();
                    if (current !== token) return;
                    if (!res.ok) {
                        reset(json.errors?.additional_days?.[0] ?? 'Gagal menghitung biaya.', true);
                        return;
                    }
                    const d = json.data;
                    $('bd-new-end').textContent = d.new_end_date_label;
                    $('bd-days').textContent = `${d.additional_days} hari`;
                    $('bd-monthly-label').textContent = `${d.monthly_blocks} blok bulan × (${cfg.monthlyMultiplier} × harian)`;
                    $('bd-daily-label').textContent = `${d.daily_days} hari × ${rupiah(cfg.dailyPrice)}`;
                    $('bd-monthly').textContent = rupiah(d.monthly_subtotal);
                    $('bd-daily').textContent = rupiah(d.daily_subtotal);
                    $('bd-total').textContent = rupiah(d.cost);
                    if (d.conflict_message) {
                        // Peringatan bentrok dengan booking lain (server tetap menolak saat disimpan).
                        const box = document.createElement('span');
                        box.className = 'text-danger fw-semibold';
                        box.textContent = d.conflict_message;
                        $('bd-status').replaceChildren(box);
                    } else {
                        $('bd-status').innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Dihitung oleh sistem.</span>';
                    }
                } catch (e) {
                    if (current === token) reset('Gagal menghitung biaya. Coba lagi.', true);
                }
            }

            // Tambahan hari -> tanggal selesai baru
            daysInput.addEventListener('input', () => {
                const days = parseInt(daysInput.value, 10);
                dateInput.value = Number.isInteger(days) && days >= 1 ? toIso(oldEnd + days * DAY) : '';
                schedulePreview();
            });

            // Tanggal selesai baru -> tambahan hari
            dateInput.addEventListener('input', () => {
                const picked = parseIso(dateInput.value);
                if (picked === null) {
                    daysInput.value = '';
                } else {
                    const diff = Math.round((picked - oldEnd) / DAY);
                    daysInput.value = diff >= 1 ? diff : '';
                }
                schedulePreview();
            });

            // Isi ulang setelah gagal validasi
            if (daysInput.value === '' && dateInput.value !== '') {
                dateInput.dispatchEvent(new Event('input'));
            } else {
                schedulePreview();
            }
        })();
    </script>
@endpush
