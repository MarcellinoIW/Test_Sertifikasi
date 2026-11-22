<?php

use App\Models\Rental;
use App\Services\RentalExtensionService;
use App\Services\RentalService;
use Carbon\Carbon;
use Tests\TestCase;

// TestCase dibutuhkan untuk membuat service lewat container dan membaca config.
uses(TestCase::class);

beforeEach(function () {
    $this->rentalService = app(RentalService::class);
    $this->extensionService = app(RentalExtensionService::class);
});

describe('Format nomor plat (normalizePlate)', function () {
    it('mengubah huruf kecil tanpa spasi menjadi format seragam: "b1234cd" -> "B 1234 CD"', function () {
        expect($this->rentalService->normalizePlate('b1234cd'))->toBe('B 1234 CD');
    });

    it('merapikan spasi berlebih: "  d  55   xy " -> "D 55 XY"', function () {
        expect($this->rentalService->normalizePlate('  d  55   xy '))->toBe('D 55 XY');
    });

    it('plat tanpa huruf belakang tetap valid: "AB 123" -> "AB 123"', function () {
        expect($this->rentalService->normalizePlate('ab123'))->toBe('AB 123');
    });

    it('plat kosong atau null menghasilkan null', function () {
        expect($this->rentalService->normalizePlate(''))->toBeNull()
            ->and($this->rentalService->normalizePlate('   '))->toBeNull()
            ->and($this->rentalService->normalizePlate(null))->toBeNull();
    });
});

describe('Rentang tanggal mulai sewa', function () {
    it('tanggal mulai paling awal adalah hari ini (jam diabaikan)', function () {
        $earliest = $this->rentalService->earliestStartDate(Carbon::create(2026, 11, 21, 15, 30));

        expect($earliest->toDateTimeString())->toBe('2026-11-21 00:00:00');
    });

    it('tanggal mulai paling akhir adalah hari ini + 30 hari', function () {
        $latest = $this->rentalService->latestStartDate(Carbon::create(2026, 11, 21));

        expect($latest->toDateString())->toBe('2026-12-21');
    });
});

describe('Konversi tanggal selesai baru menjadi tambahan hari (daysUntil)', function () {
    beforeEach(function () {
        $this->rental = new Rental(['start_date' => '2026-11-21', 'end_date' => '2026-11-25']);
    });

    it('selesai 25 Nov diperpanjang sampai 9 Jan 2027 = 45 hari', function () {
        expect($this->extensionService->daysUntil($this->rental, Carbon::create(2027, 1, 9)))->toBe(45);
    });

    it('tanggal sama dengan tanggal selesai lama = 0 hari', function () {
        expect($this->extensionService->daysUntil($this->rental, Carbon::create(2026, 11, 25)))->toBe(0);
    });

    it('tanggal sebelum tanggal selesai lama menghasilkan angka negatif', function () {
        expect($this->extensionService->daysUntil($this->rental, Carbon::create(2026, 11, 23)))->toBe(-2);
    });
});
