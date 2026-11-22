<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// seeder utama yang menjalankan semua seeder
class DatabaseSeeder extends Seeder
{
    // function untuk menjalankan semua seeder
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
