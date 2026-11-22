<?php

namespace App\Services;

use App\Enums\GarageType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// service untuk membuat laporan sewa
class ReportService
{
    // function untuk membuat laporan per bulan
    public function monthly(int $year): array
    {
        $start = CarbonImmutable::create($year, 1, 1);
        $end = $start->endOfYear();

        $rentals = $this->rentalQuery($start, $end)
            ->selectRaw($this->datePart('rentals.start_date', 'month').' as period')
            ->groupByRaw($this->datePart('rentals.start_date', 'month'))
            ->get()
            ->keyBy('period');

        $extensions = $this->extensionQuery($start, $end)
            ->selectRaw($this->datePart('rental_extensions.created_at', 'month').' as period')
            ->groupByRaw($this->datePart('rental_extensions.created_at', 'month'))
            ->get()
            ->keyBy('period');

        $rows = [];
        foreach (range(1, 12) as $month) {
            $rows[] = $this->makeRow(
                CarbonImmutable::create($year, $month, 1)->translatedFormat('F'),
                $rentals->get($month),
                $extensions->get($month),
            );
        }

        return $this->makeReport("Laporan Per Bulan Tahun {$year}", 'Bulan', $rows);
    }

    // function untuk membuat laporan per hari
    public function daily(int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1);
        $end = $start->endOfMonth();

        $rentals = $this->rentalQuery($start, $end)
            ->selectRaw($this->datePart('rentals.start_date', 'day').' as period')
            ->groupByRaw($this->datePart('rentals.start_date', 'day'))
            ->get()
            ->keyBy('period');

        $extensions = $this->extensionQuery($start, $end)
            ->selectRaw($this->datePart('rental_extensions.created_at', 'day').' as period')
            ->groupByRaw($this->datePart('rental_extensions.created_at', 'day'))
            ->get()
            ->keyBy('period');

        $rows = [];
        foreach (range(1, $start->daysInMonth) as $day) {
            $rows[] = $this->makeRow(
                $start->setDay($day)->translatedFormat('d M Y'),
                $rentals->get($day),
                $extensions->get($day),
            );
        }

        return $this->makeReport('Laporan Per Hari Bulan '.$start->translatedFormat('F Y'), 'Tanggal', $rows);
    }

    // function untuk membuat laporan per tipe garasi
    public function byGarageType(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $rentals = $this->rentalQuery($from, $to)
            ->join('garages', 'garages.id', '=', 'rentals.garage_id')
            ->addSelect('garages.type as period')
            ->groupBy('garages.type')
            ->get()
            ->keyBy('period');

        $extensions = $this->extensionQuery($from, $to)
            ->join('rentals', 'rentals.id', '=', 'rental_extensions.rental_id')
            ->join('garages', 'garages.id', '=', 'rentals.garage_id')
            ->addSelect('garages.type as period')
            ->groupBy('garages.type')
            ->get()
            ->keyBy('period');

        $rows = array_map(fn (GarageType $type) => $this->makeRow(
            "{$type->prefix()} · {$type->label()}",
            $rentals->get($type->value),
            $extensions->get($type->value),
        ), GarageType::cases());

        return $this->makeReport('Laporan Per Tipe Garasi · '.$this->periodLabel($from, $to), 'Tipe Garasi', $rows);
    }

    // function untuk membuat laporan per penyewa
    public function byTenant(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $rentals = $this->rentalQuery($from, $to)
            ->join('users', 'users.id', '=', 'rentals.user_id')
            ->addSelect('users.id as user_id', 'users.name', 'users.email')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->get()
            ->keyBy('user_id');

        $extensions = $this->extensionQuery($from, $to)
            ->join('rentals', 'rentals.id', '=', 'rental_extensions.rental_id')
            ->join('users', 'users.id', '=', 'rentals.user_id')
            ->addSelect('users.id as user_id', 'users.name', 'users.email')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->get()
            ->keyBy('user_id');

        $rows = $rentals->keys()
            ->merge($extensions->keys())
            ->unique()
            ->map(function ($userId) use ($rentals, $extensions) {
                $info = $rentals->get($userId) ?? $extensions->get($userId);

                return $this->makeRow($info->name, $rentals->get($userId), $extensions->get($userId), $info->email);
            })
            ->sortBy([
                fn (array $a, array $b) => $b['revenue'] <=> $a['revenue'],
                fn (array $a, array $b) => strcmp($a['label'], $b['label']),
            ])
            ->values()
            ->all();

        return $this->makeReport('Laporan Per Penyewa · '.$this->periodLabel($from, $to), 'Penyewa', $rows);
    }

    // function untuk query data sewa yang dipakai di laporan
    private function rentalQuery(?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return DB::table('rentals')
            ->selectRaw('COUNT(*) as rental_count')
            ->selectRaw('COALESCE(SUM(rentals.rent_cost), 0) as rent_cost')
            ->selectRaw('COALESCE(SUM(rentals.electricity_cost), 0) as electricity_cost')
            ->when($from, fn (Builder $q) => $q->where('rentals.start_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $q) => $q->where('rentals.start_date', '<=', $to->toDateString()));
    }

    // function untuk query data perpanjangan yang dipakai di laporan
    private function extensionQuery(?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        return DB::table('rental_extensions')
            ->selectRaw('COUNT(*) as extension_count')
            ->selectRaw('COALESCE(SUM(rental_extensions.cost), 0) as extension_cost')
            ->when($from, fn (Builder $q) => $q->where('rental_extensions.created_at', '>=', $from->copy()->startOfDay()->toDateTimeString()))
            ->when($to, fn (Builder $q) => $q->where('rental_extensions.created_at', '<=', $to->copy()->endOfDay()->toDateTimeString()));
    }

    // function untuk mengambil bulan / hari dari kolom tanggal
    private function datePart(string $column, string $part): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('".($part === 'month' ? '%m' : '%d')."', {$column}) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT('.strtoupper($part)." FROM {$column}) AS INTEGER)",
            default => strtoupper($part)."({$column})",
        };
    }

    // function untuk membuat satu baris laporan
    private function makeRow(string $label, ?object $rental, ?object $extension, ?string $subLabel = null): array
    {
        return $this->row(
            label: $label,
            rentalCount: (int) ($rental->rental_count ?? 0),
            extensionCount: (int) ($extension->extension_count ?? 0),
            rentCost: (float) ($rental->rent_cost ?? 0),
            electricityCost: (float) ($rental->electricity_cost ?? 0),
            extensionCost: (float) ($extension->extension_cost ?? 0),
            subLabel: $subLabel,
        );
    }

    // function untuk menyusun isi baris laporan
    private function row(
        string $label,
        int $rentalCount = 0,
        int $extensionCount = 0,
        float $rentCost = 0.0,
        float $electricityCost = 0.0,
        float $extensionCost = 0.0,
        ?string $subLabel = null,
    ): array {
        return [
            'label' => $label,
            'sub_label' => $subLabel,
            'rental_count' => $rentalCount,
            'extension_count' => $extensionCount,
            'transaction_count' => $rentalCount + $extensionCount,
            'rent_cost' => $rentCost,
            'electricity_cost' => $electricityCost,
            'extension_cost' => $extensionCost,
            'revenue' => round($rentCost + $electricityCost + $extensionCost, 2),
        ];
    }

    // function untuk menjumlahkan total laporan
    private function sumRows(array $rows): array
    {
        $rows = collect($rows);

        return $this->row(
            label: 'Total',
            rentalCount: (int) $rows->sum('rental_count'),
            extensionCount: (int) $rows->sum('extension_count'),
            rentCost: round((float) $rows->sum('rent_cost'), 2),
            electricityCost: round((float) $rows->sum('electricity_cost'), 2),
            extensionCost: round((float) $rows->sum('extension_cost'), 2),
        );
    }

    // function untuk menyusun hasil laporan
    private function makeReport(string $title, string $labelHeading, array $rows): array
    {
        return [
            'title' => $title,
            'label_heading' => $labelHeading,
            'rows' => $rows,
            'total' => $this->sumRows($rows),
        ];
    }

    // function untuk membuat label periode laporan
    private function periodLabel(?CarbonInterface $from, ?CarbonInterface $to): string
    {
        return match (true) {
            $from && $to => $from->translatedFormat('d M Y').' – '.$to->translatedFormat('d M Y'),
            (bool) $from => 'Sejak '.$from->translatedFormat('d M Y'),
            (bool) $to => 'Sampai '.$to->translatedFormat('d M Y'),
            default => 'Semua periode',
        };
    }

    // function untuk mengambil daftar tahun di filter laporan
    public function availableYears(): Collection
    {
        $range = DB::table('rentals')
            ->selectRaw('MIN(start_date) as first_date, MAX(start_date) as last_date')
            ->first();

        $current = (int) now()->year;
        $first = $range?->first_date ? min($current, CarbonImmutable::parse($range->first_date)->year) : $current;
        $last = $range?->last_date ? max($current, CarbonImmutable::parse($range->last_date)->year) : $current;

        return collect(range($last, $first));
    }
}
