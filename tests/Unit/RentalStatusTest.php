<?php

use App\Models\Rental;
use App\Models\RentalExtension;
use Carbon\Carbon;
use Tests\TestCase;

// TestCase dibutuhkan karena model memakai cast tanggal Laravel (tanpa database).
uses(TestCase::class);

// Sewa 21 Nov - 24 Nov 2026 (tanggal selesai eksklusif).
beforeEach(function () {
    $this->rental = new Rental([
        'start_date' => '2026-11-21',
        'end_date' => '2026-11-24',
        'total_cost' => 60000,
    ]);
});

describe('Status sewa berdasarkan tanggal (statusOn)', function () {
    it('sebelum tanggal mulai berstatus akan datang (upcoming)', function () {
        expect($this->rental->statusOn(Carbon::create(2026, 11, 20)))->toBe('upcoming');
    });

    it('tepat di tanggal mulai berstatus aktif (active)', function () {
        expect($this->rental->statusOn(Carbon::create(2026, 11, 21)))->toBe('active');
    });

    it('sehari sebelum tanggal selesai masih aktif', function () {
        expect($this->rental->statusOn(Carbon::create(2026, 11, 23, 23, 59)))->toBe('active');
    });

    it('tepat di tanggal selesai berstatus selesai (finished)', function () {
        expect($this->rental->statusOn(Carbon::create(2026, 11, 24)))->toBe('finished');
    });

    it('isActiveOn bernilai true hanya saat status aktif', function () {
        expect($this->rental->isActiveOn(Carbon::create(2026, 11, 22)))->toBeTrue()
            ->and($this->rental->isActiveOn(Carbon::create(2026, 11, 20)))->toBeFalse()
            ->and($this->rental->isActiveOn(Carbon::create(2026, 11, 24)))->toBeFalse();
    });
});

describe('Total bayar penyewa (grandTotal)', function () {
    it('tanpa perpanjangan, total bayar = total biaya sewa', function () {
        $this->rental->setRelation('extensions', collect());

        expect($this->rental->grandTotal())->toBe(60000.0);
    });

    it('total bayar = total biaya sewa + seluruh biaya perpanjangan', function () {
        $this->rental->setRelation('extensions', collect([
            new RentalExtension(['cost' => 40000]),
            new RentalExtension(['cost' => 15000]),
        ]));

        expect($this->rental->grandTotal())->toBe(115000.0);
    });
});
