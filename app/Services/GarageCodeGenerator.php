<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Exceptions\BusinessRuleException;
use App\Models\Garage;

/**
 * Pembuat kode garasi otomatis: prefix tipe + 3 digit (S001, C010, E005),
 * melanjutkan nomor terbesar yang sudah ada untuk tipe tersebut.
 */
class GarageCodeGenerator
{
    /**
     * Jumlah digit nomor urut pada kode garasi.
     */
    private const DIGITS = 3;

    /**
     * Menghasilkan sejumlah kode garasi berikutnya untuk satu tipe.
     *
     * Sebaiknya dipanggil di dalam transaksi DB dengan $lock = true agar
     * dua batch yang berjalan bersamaan tidak menghasilkan kode yang sama.
     *
     * @param  GarageType  $type  Tipe garasi.
     * @param  int  $qty  Jumlah kode yang dibutuhkan (minimal 1).
     * @param  bool  $lock  Kunci baris garasi tipe ini (SELECT ... FOR UPDATE).
     * @return array<int, string> Daftar kode berurutan, misal ["C010", "C011"].
     *
     * @throws BusinessRuleException Jika nomor terakhir + jumlah melebihi batas (999).
     */
    public function nextCodes(GarageType $type, int $qty, bool $lock = false): array
    {
        $last = $this->lastNumber($type, $lock);
        $max = $this->maxNumber();

        if ($qty < 1 || $last + $qty > $max) {
            throw new BusinessRuleException(
                "Jumlah melebihi batas. Garasi {$type->label()} terakhir bernomor {$this->format($type, $last)}, "
                ."sisa kuota hanya {$this->remaining($type)} garasi (maksimal {$this->format($type, $max)}).",
                'quantity',
            );
        }

        return array_map(
            fn (int $number) => $this->format($type, $number),
            range($last + 1, $last + $qty),
        );
    }

    /**
     * Nomor urut terbesar yang sudah dipakai untuk tipe garasi (0 jika belum ada).
     *
     * @param  GarageType  $type  Tipe garasi.
     * @param  bool  $lock  Kunci baris garasi tipe ini selama transaksi.
     * @return int
     */
    public function lastNumber(GarageType $type, bool $lock = false): int
    {
        $query = Garage::query()->where('code', 'like', $type->prefix().'%');

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastCode = $query->max('code');

        return $lastCode ? (int) substr((string) $lastCode, strlen($type->prefix())) : 0;
    }

    /**
     * Sisa kuota nomor yang masih bisa dibuat untuk tipe garasi.
     *
     * @param  GarageType  $type  Tipe garasi.
     * @return int
     */
    public function remaining(GarageType $type): int
    {
        return max(0, $this->maxNumber() - $this->lastNumber($type));
    }

    /**
     * Nomor urut terakhir untuk semua tipe, dipakai sebagai preview di form.
     *
     * @return array<string, int> Kunci = nilai GarageType, nilai = nomor terakhir.
     */
    public function lastNumbers(): array
    {
        $result = [];
        foreach (GarageType::cases() as $type) {
            $result[$type->value] = $this->lastNumber($type);
        }

        return $result;
    }

    /**
     * Format nomor menjadi kode garasi, misal (Car, 10) -> "C010".
     *
     * @param  GarageType  $type  Tipe garasi.
     * @param  int  $number  Nomor urut.
     * @return string
     */
    public function format(GarageType $type, int $number): string
    {
        return $type->prefix().str_pad((string) $number, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Nomor urut maksimum per tipe dari config.
     *
     * @return int
     */
    private function maxNumber(): int
    {
        return (int) config('garasi.max_garages_per_batch');
    }
}
