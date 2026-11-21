{{-- Tabel sewa penyewa beserta riwayat perpanjangannya. Variabel: $rentals, $emptyText --}}
<div class="table-responsive">
    <table class="table mb-0">
        <thead class="table-light">
            <tr>
                <th>Garasi</th>
                <th>Kendaraan</th>
                <th>Jenis</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th class="text-end">Total Bayar</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rentals as $rental)
                <tr>
                    <td>
                        <span class="badge text-bg-primary">{{ $rental->garage->code }}</span>
                        <div class="small text-muted">{{ $rental->garage->type->label() }}</div>
                    </td>
                    <td>{{ $rental->vehicle->displayName() }}</td>
                    <td>{{ $rental->rental_type->label() }} · {{ $rental->days }} hari</td>
                    <td>@tanggal($rental->start_date)</td>
                    <td>
                        @tanggal($rental->end_date)
                        @if ($rental->extensions->isNotEmpty())
                            <span class="badge text-bg-info ms-1" title="Pernah diperpanjang">
                                <i class="bi bi-calendar-plus"></i> {{ $rental->extensions->count() }}×
                            </span>
                        @endif
                    </td>
                    <td class="text-end">@rupiah($rental->grandTotal())</td>
                    <td class="text-end">
                        <a href="{{ route('tenant.rentals.show', $rental) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                    </td>
                </tr>
                @foreach ($rental->extensions as $extension)
                    <tr class="table-light small">
                        <td></td>
                        <td colspan="3" class="text-muted">
                            <i class="bi bi-arrow-return-right me-1"></i>
                            Perpanjangan @tanggal($extension->created_at): +{{ $extension->additional_days }} hari
                            ({{ $extension->monthly_blocks }} bulan + {{ $extension->daily_days }} hari harian)
                        </td>
                        <td class="text-muted">@tanggal($extension->old_end_date) → @tanggal($extension->new_end_date)</td>
                        <td class="text-end text-muted">@rupiah($extension->cost)</td>
                        <td></td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ $emptyText }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
