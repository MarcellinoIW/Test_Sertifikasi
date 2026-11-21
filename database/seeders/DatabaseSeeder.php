<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama: admin (wajib) lalu data demo.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seluruh seeder aplikasi.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
