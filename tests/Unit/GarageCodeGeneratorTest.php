<?php

use App\Enums\GarageType;
use App\Exceptions\BusinessRuleException;
use App\Models\Garage;
use App\Services\GarageCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->generator = new GarageCodeGenerator;
});

it('menghasilkan kode pertama S001 jika belum ada garasi sepeda', function () {
    expect($this->generator->nextCodes(GarageType::Bicycle, 1))->toBe(['S001']);
});

it('menghasilkan kode berurutan sesuai jumlah yang diminta', function () {
    expect($this->generator->nextCodes(GarageType::Electric, 3))->toBe(['E001', 'E002', 'E003']);
});

it('melanjutkan nomor terbesar: setelah C009 menghasilkan C010', function () {
    foreach (range(1, 9) as $n) {
        Garage::factory()->car()->create(['code' => sprintf('C%03d', $n)]);
    }

    expect($this->generator->nextCodes(GarageType::Car, 2))->toBe(['C010', 'C011']);
});

it('penomoran setiap tipe garasi berdiri sendiri', function () {
    Garage::factory()->car()->create(['code' => 'C050']);

    expect($this->generator->nextCodes(GarageType::Bicycle, 1))->toBe(['S001'])
        ->and($this->generator->nextCodes(GarageType::Car, 1))->toBe(['C051']);
});

it('mengizinkan batch yang tepat mencapai nomor 999', function () {
    Garage::factory()->bicycle()->create(['code' => 'S997']);

    expect($this->generator->nextCodes(GarageType::Bicycle, 2))->toBe(['S998', 'S999']);
});

it('menolak batch jika nomor terakhir ditambah jumlah melebihi 999', function () {
    Garage::factory()->bicycle()->create(['code' => 'S998']);

    $this->generator->nextCodes(GarageType::Bicycle, 2);
})->throws(BusinessRuleException::class, 'sisa kuota hanya 1 garasi');

it('menolak batch 1000 garasi sekaligus', function () {
    $this->generator->nextCodes(GarageType::Car, 1000);
})->throws(BusinessRuleException::class);
