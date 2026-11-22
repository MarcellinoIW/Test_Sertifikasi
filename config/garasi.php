<?php

/*
|--------------------------------------------------------------------------
| Konstanta Bisnis Aplikasi Sewa Garasi
|--------------------------------------------------------------------------
|
| Semua angka aturan bisnis disimpan di sini agar tidak tersebar
| (hard-coded) di controller, service, maupun view.
|
*/

return [

    /*
     * Kapasitas baterai mobil listrik (kWh). Dipakai untuk menghitung
     * jumlah kWh yang diisi: kapasitas - sisa baterai.
     */
    'battery_capacity_kwh' => 100,

    /*
     * Pengali harga harian untuk sewa bulanan / satu blok bulan
     * perpanjangan. Harga bulanan = monthly_multiplier x harga harian.
     */
    'monthly_multiplier' => 25,

    /*
     * Nomor urut maksimum kode garasi per tipe (3 digit: 001-999),
     * sekaligus batas jumlah garasi dalam satu batch.
     */
    'max_garages_per_batch' => 999,

    /*
     * Ukuran maksimal setiap upload gambar (bukti bayar), dalam kilobyte.
     * Dipakai oleh validasi server (Form Request) dan pemeriksaan di browser
     * (komponen x-form.input type="file"). Pastikan post_max_size dan
     * upload_max_filesize di php.ini LEBIH BESAR dari nilai ini.
     */
    'max_upload_kb' => 5120,

    /*
     * Penyewa boleh memilih tanggal mulai sewa dari hari ini sampai
     * sekian hari ke depan.
     */
    'max_start_days_ahead' => 30,

];
