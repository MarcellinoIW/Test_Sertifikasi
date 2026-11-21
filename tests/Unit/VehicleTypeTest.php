<?php

use App\Enums\GarageType;
use App\Enums\VehicleType;

describe('Kompatibilitas kendaraan dengan tipe garasi', function () {
    it('sepeda dan sepeda motor hanya boleh di garasi sepeda (S)', function (VehicleType $vehicle) {
        expect($vehicle->allowedGarageTypes())->toBe([GarageType::Bicycle])
            ->and($vehicle->canUseGarage(GarageType::Car))->toBeFalse()
            ->and($vehicle->canUseGarage(GarageType::Electric))->toBeFalse();
    })->with([VehicleType::Bicycle, VehicleType::Motorcycle]);

    it('mobil bensin hanya boleh di garasi mobil (C)', function () {
        expect(VehicleType::Car->allowedGarageTypes())->toBe([GarageType::Car])
            ->and(VehicleType::Car->canUseGarage(GarageType::Electric))->toBeFalse();
    });

    it('mobil listrik boleh di garasi mobil (C) dan garasi mobil listrik (E)', function () {
        expect(VehicleType::ElectricCar->allowedGarageTypes())->toBe([GarageType::Car, GarageType::Electric])
            ->and(VehicleType::ElectricCar->canUseGarage(GarageType::Bicycle))->toBeFalse();
    });

    it('garasi mobil listrik (E) hanya menerima mobil listrik', function () {
        $allowed = array_filter(VehicleType::cases(), fn (VehicleType $v) => $v->canUseGarage(GarageType::Electric));

        expect(array_values($allowed))->toBe([VehicleType::ElectricCar]);
    });
});

describe('Aturan nomor plat', function () {
    it('sepeda tidak wajib nomor plat, kendaraan lain wajib', function () {
        expect(VehicleType::Bicycle->requiresPlate())->toBeFalse()
            ->and(VehicleType::Motorcycle->requiresPlate())->toBeTrue()
            ->and(VehicleType::Car->requiresPlate())->toBeTrue()
            ->and(VehicleType::ElectricCar->requiresPlate())->toBeTrue();
    });
});

describe('Prefix kode garasi', function () {
    it('prefix S untuk sepeda, C untuk mobil, E untuk mobil listrik', function () {
        expect(GarageType::Bicycle->prefix())->toBe('S')
            ->and(GarageType::Car->prefix())->toBe('C')
            ->and(GarageType::Electric->prefix())->toBe('E');
    });
});
