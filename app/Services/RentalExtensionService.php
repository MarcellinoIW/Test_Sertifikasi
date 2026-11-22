<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

// service untuk logika perpanjangan sewa
class RentalExtensionService
{
    // folder penyimpanan bukti bayar perpanjangan
    public const PROOF_DIRECTORY = 'payment-proofs/extensions';

    // function constructor untuk memanggil kalkulator harga dan GarageService
    public function __construct(
        private readonly RentalPriceCalculator $calculator,
        private readonly GarageService $garageService,
    ) {}

    // function untuk mengambil daftar sewa yang sedang aktif
    public function activeRentals(?string $search = null, ?CarbonInterface $date = null, int $perPage = 20): LengthAwarePaginator
    {
        $date ??= today();
        $this->garageService->syncStatus($date);

        $search = $search !== null ? trim($search) : null;

        return Rental::query()
            ->activeOn($date)
            ->with(['garage', 'user', 'vehicle'])
            ->withCount('extensions')
            ->when($search, function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->whereHas('garage', fn ($q) => $q->where('code', 'like', $like))
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $like))
                        ->orWhereHas('vehicle', fn ($q) => $q->where('plate_number', 'like', $like));
                });
            })
            ->orderBy('end_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    // function untuk menghitung rincian biaya perpanjangan
    public function preview(Rental $rental, int $additionalDays): array
    {
        return $this->calculator->extensionCost($rental->end_date, $additionalDays, (float) $rental->daily_price);
    }

    // function untuk menghitung jumlah tambahan hari dari tanggal selesai baru
    public function daysUntil(Rental $rental, CarbonInterface $newEndDate): int
    {
        $oldEnd = CarbonImmutable::instance($rental->end_date)->startOfDay();

        return (int) $oldEnd->diffInDays(CarbonImmutable::instance($newEndDate)->startOfDay(), false);
    }

    // function untuk menyimpan perpanjangan sewa
    public function extend(Rental $rental, User $admin, int $additionalDays, UploadedFile $paymentProof, ?CarbonInterface $today = null): RentalExtension
    {
        $today ??= today();

        if ($additionalDays < 1) {
            throw ValidationException::withMessages(['additional_days' => 'Tambahan hari minimal 1 hari.']);
        }

        $proofPath = $paymentProof->store(self::PROOF_DIRECTORY, 'public');

        try {
            return DB::transaction(function () use ($rental, $admin, $additionalDays, $proofPath, $today) {
                $locked = Rental::query()->lockForUpdate()->findOrFail($rental->id);

                // Kunci juga baris garasi: proses booking penyewa mengunci baris yang sama,
                // sehingga perpanjangan dan booking baru di garasi ini tidak bisa balapan.
                Garage::query()->lockForUpdate()->findOrFail($locked->garage_id);

                if (! $locked->isActiveOn($today)) {
                    // Dicek ulang setelah dikunci (controller sudah mengecek lebih dulu).
                    throw ValidationException::withMessages(['additional_days' => 'Hanya sewa yang sedang aktif hari ini yang dapat diperpanjang.']);
                }

                $breakdown = $this->preview($locked, $additionalDays);

                $this->ensureNoOverlap($locked, $breakdown);

                $extension = RentalExtension::query()->create([
                    'rental_id' => $locked->id,
                    'admin_id' => $admin->id,
                    'old_end_date' => $breakdown['old_end_date']->toDateString(),
                    'new_end_date' => $breakdown['new_end_date']->toDateString(),
                    'additional_days' => $breakdown['additional_days'],
                    'monthly_blocks' => $breakdown['monthly_blocks'],
                    'daily_days' => $breakdown['daily_days'],
                    'cost' => $breakdown['cost'],
                    'payment_proof' => $proofPath,
                ]);

                $locked->update(['end_date' => $breakdown['new_end_date']->toDateString()]);

                return $extension;
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($proofPath);

            throw $e;
        }
    }

    // function untuk mengecek apakah perpanjangan bentrok dengan sewa lain
    public function conflictMessage(Rental $rental, array $breakdown): ?string
    {
        $conflict = Rental::query()
            ->where('garage_id', $rental->garage_id)
            ->whereKeyNot($rental->id)
            ->overlapping($breakdown['old_end_date'], $breakdown['new_end_date'])
            ->orderBy('start_date')
            ->first();

        if ($conflict === null) {
            return null;
        }

        return 'Garasi sudah dibooking penyewa lain mulai tanggal '.$conflict->start_date->translatedFormat('d M Y')
            .'. Perpanjangan maksimal sampai tanggal tersebut.';
    }

    // function untuk memastikan perpanjangan tidak bentrok dengan sewa lain
    private function ensureNoOverlap(Rental $rental, array $breakdown): void
    {
        $message = $this->conflictMessage($rental, $breakdown);

        if ($message !== null) {
            throw ValidationException::withMessages(['additional_days' => $message]);
        }
    }
}
