<?php

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Exceptions\BusinessRuleException;
use App\Services\RentalPriceCalculator;
use Carbon\Carbon;

beforeEach(function () {
    $this->calc = new RentalPriceCalculator(monthlyMultiplier: 25, batteryCapacityKwh: 100);
});

describe('Batas hari sewa harian (maxDailyDays)', function () {
    it('November (30 hari) maksimal 29 hari', function () {
        expect($this->calc->maxDailyDays(Carbon::create(2026, 11, 21)))->toBe(29);
    });

    it('Januari (31 hari) maksimal 30 hari', function () {
        expect($this->calc->maxDailyDays(Carbon::create(2027, 1, 10)))->toBe(30);
    });

    it('Februari 2027 (bukan kabisat, 28 hari) maksimal 27 hari', function () {
        expect($this->calc->maxDailyDays(Carbon::create(2027, 2, 1)))->toBe(27);
    });

    it('Februari 2028 (kabisat, 29 hari) maksimal 28 hari', function () {
        expect($this->calc->maxDailyDays(Carbon::create(2028, 2, 15)))->toBe(28);
    });
});

describe('Jumlah hari sewa bulanan (monthlyDays)', function () {
    it('Februari 2028 (kabisat) bernilai 29 hari', function () {
        expect($this->calc->monthlyDays(Carbon::create(2028, 2, 1)))->toBe(29);
    });

    it('November bernilai 30 hari', function () {
        expect($this->calc->monthlyDays(Carbon::create(2026, 11, 21)))->toBe(30);
    });
});

describe('Biaya sewa', function () {
    it('biaya harian = jumlah hari x harga harian', function () {
        expect($this->calc->rentCost(RentalType::Daily, 7, 20000))->toBe(140000.0);
    });

    it('biaya bulanan = 25 x harga harian, tidak bergantung jumlah hari', function () {
        expect($this->calc->rentCost(RentalType::Monthly, 30, 20000))->toBe(500000.0)
            ->and($this->calc->rentCost(RentalType::Monthly, 29, 20000))->toBe(500000.0);
    });

    it('quote harian: mulai 21 Nov selama 1 hari selesai 22 Nov', function () {
        $quote = $this->calc->quote(RentalType::Daily, 1, Carbon::create(2026, 11, 21), 20000, VehicleType::Car, GarageType::Car);

        expect($quote->endDate->toDateString())->toBe('2026-11-22')
            ->and($quote->rentCost)->toBe(20000.0)
            ->and($quote->totalCost)->toBe(20000.0);
    });

    it('quote bulanan Februari 2028 berisi 29 hari dengan biaya 25 x harian', function () {
        $quote = $this->calc->quote(RentalType::Monthly, null, Carbon::create(2028, 2, 1), 20000, VehicleType::Car, GarageType::Car);

        expect($quote->days)->toBe(29)
            ->and($quote->endDate->toDateString())->toBe('2028-03-01')
            ->and($quote->rentCost)->toBe(500000.0);
    });

    it('quote harian menolak jumlah hari melebihi batas bulan berjalan', function () {
        $this->calc->quote(RentalType::Daily, 30, Carbon::create(2026, 11, 21), 20000, VehicleType::Car, GarageType::Car);
    })->throws(BusinessRuleException::class, 'antara 1 sampai 29 hari');
});

describe('Biaya isi listrik', function () {
    it('sisa baterai 30 kWh x Rp 2.500 menghasilkan Rp 175.000', function () {
        expect($this->calc->electricityCost(VehicleType::ElectricCar, GarageType::Electric, 30, 2500))->toBe(175000.0)
            ->and($this->calc->chargedKwh(30))->toBe(70.0);
    });

    it('mobil listrik di garasi mobil (C) tidak dikenai biaya listrik', function () {
        expect($this->calc->electricityCost(VehicleType::ElectricCar, GarageType::Car, 30, 2500))->toBe(0.0);
    });

    it('baterai penuh (100 kWh) tidak dikenai biaya listrik', function () {
        expect($this->calc->electricityCost(VehicleType::ElectricCar, GarageType::Electric, 100, 2500))->toBe(0.0);
    });

    it('total quote mobil listrik di garasi E = biaya sewa + biaya listrik', function () {
        $quote = $this->calc->quote(RentalType::Daily, 2, Carbon::create(2026, 11, 21), 30000, VehicleType::ElectricCar, GarageType::Electric, 30, 2500);

        expect($quote->rentCost)->toBe(60000.0)
            ->and($quote->chargedKwh)->toBe(70.0)
            ->and($quote->electricityCost)->toBe(175000.0)
            ->and($quote->totalCost)->toBe(235000.0);
    });

    it('quote mobil listrik di garasi C tidak menyimpan kWh maupun harga per kWh', function () {
        $quote = $this->calc->quote(RentalType::Daily, 2, Carbon::create(2026, 11, 21), 20000, VehicleType::ElectricCar, GarageType::Car, 30, null);

        expect($quote->electricityCost)->toBe(0.0)
            ->and($quote->chargedKwh)->toBeNull()
            ->and($quote->electricityPricePerKwh)->toBeNull()
            ->and($quote->batteryRemainingKwh)->toBe(30.0);
    });
});

describe('Biaya perpanjangan (extensionCost)', function () {
    it('selesai 25 Des + 45 hari = 1 blok bulan + 14 hari harian', function () {
        $b = $this->calc->extensionCost(Carbon::create(2026, 12, 25), 45, 20000);

        expect($b->monthlyBlocks)->toBe(1)
            ->and($b->dailyDays)->toBe(14)
            ->and($b->cost)->toBe(25 * 20000 + 14 * 20000.0)
            ->and($b->newEndDate->toDateString())->toBe('2027-02-08');
    });

    it('selesai 10 Nov + 20 hari = 0 blok bulan + 20 hari harian', function () {
        $b = $this->calc->extensionCost(Carbon::create(2026, 11, 10), 20, 20000);

        expect($b->monthlyBlocks)->toBe(0)
            ->and($b->dailyDays)->toBe(20)
            ->and($b->cost)->toBe(400000.0);
    });

    it('lintas Februari kabisat: 10 Feb 2028 + 29 hari = tepat 1 blok bulan', function () {
        $b = $this->calc->extensionCost(Carbon::create(2028, 2, 10), 29, 20000);

        expect($b->monthlyBlocks)->toBe(1)
            ->and($b->dailyDays)->toBe(0)
            ->and($b->cost)->toBe(500000.0);
    });

    it('lintas Februari kabisat: 10 Feb 2028 + 28 hari masih dihitung harian', function () {
        $b = $this->calc->extensionCost(Carbon::create(2028, 2, 10), 28, 20000);

        expect($b->monthlyBlocks)->toBe(0)
            ->and($b->dailyDays)->toBe(28);
    });

    it('lebih dari 2 bulan: 15 Jan 2026 + 70 hari = 2 blok bulan (Jan 31, Feb 28) + 11 hari', function () {
        $b = $this->calc->extensionCost(Carbon::create(2026, 1, 15), 70, 10000);

        expect($b->monthlyBlocks)->toBe(2)
            ->and($b->dailyDays)->toBe(11)
            ->and($b->cost)->toBe(2 * 250000 + 11 * 10000.0)
            ->and($b->newEndDate->toDateString())->toBe('2026-03-26');
    });

    it('menolak tambahan hari kurang dari 1', function () {
        $this->calc->extensionCost(Carbon::create(2026, 11, 10), 0, 20000);
    })->throws(BusinessRuleException::class);
});
