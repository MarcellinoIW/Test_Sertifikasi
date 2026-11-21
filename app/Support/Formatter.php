<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Helper format tampilan: mata uang Rupiah dan tanggal.
 */
class Formatter
{
    /**
     * Format angka menjadi Rupiah tanpa desimal, misal "Rp 1.250.000".
     *
     * @param  int|float|string|null  $amount  Nominal uang.
     * @return string
     */
    public static function rupiah(int|float|string|null $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * Format tanggal menjadi "d M Y" dengan nama bulan sesuai locale, misal "21 Nov 2026".
     *
     * @param  CarbonInterface|string|null  $date  Tanggal (objek Carbon atau string).
     * @return string String kosong jika tanggal null.
     */
    public static function date(CarbonInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        return $carbon->translatedFormat('d M Y');
    }
}
