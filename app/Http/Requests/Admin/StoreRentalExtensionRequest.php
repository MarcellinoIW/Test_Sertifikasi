<?php

namespace App\Http\Requests\Admin;

use App\Models\Rental;
use App\Services\RentalExtensionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

// request untuk validasi form perpanjangan sewa
class StoreRentalExtensionRequest extends FormRequest
{
    // batas maksimal tambahan hari
    public const MAX_DAYS = 36500;

    // function untuk mengecek user boleh pakai request ini
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    // function untuk aturan validasi form perpanjangan sewa
    public function rules(): array
    {
        return [
            'additional_days' => ['nullable', 'required_without:new_end_date', 'integer', 'min:1', 'max:'.self::MAX_DAYS],
            'new_end_date' => ['nullable', 'required_without:additional_days', 'date_format:Y-m-d'],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:'.config('garasi.max_upload_kb')],
        ];
    }

    // function untuk validasi tambahan (cek tambahan hari dan tanggal selesai baru)
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['additional_days', 'new_end_date']) || ! $this->filled('new_end_date')) {
                    return;
                }

                $rental = $this->rental();
                $daysFromDate = app(RentalExtensionService::class)->daysUntil($rental, Carbon::parse($this->input('new_end_date')));

                if ($daysFromDate < 1) {
                    $validator->errors()->add(
                        'new_end_date',
                        'Tanggal selesai baru harus setelah tanggal selesai lama ('.$rental->end_date->translatedFormat('d M Y').').',
                    );

                    return;
                }

                if ($this->filled('additional_days') && $this->integer('additional_days') !== $daysFromDate) {
                    $validator->errors()->add(
                        'additional_days',
                        "Tambahan hari tidak sesuai dengan tanggal selesai baru (seharusnya {$daysFromDate} hari).",
                    );
                }
            },
        ];
    }

    // function untuk pesan error validasi
    public function messages(): array
    {
        // Label batas upload dari config, misal 5120 KB -> "5MB".
        $maxSize = str_replace('.', ',', (string) (config('garasi.max_upload_kb') / 1024)).'MB';

        return [
            'additional_days.required_without' => 'Isi tambahan hari atau tanggal selesai baru.',
            'additional_days.integer' => 'Tambahan hari harus berupa bilangan bulat.',
            'additional_days.min' => 'Tambahan hari minimal 1 hari.',
            'additional_days.max' => 'Tambahan hari terlalu besar.',
            'new_end_date.required_without' => 'Isi tanggal selesai baru atau tambahan hari.',
            'new_end_date.date_format' => 'Format tanggal selesai baru tidak valid.',
            'payment_proof.required' => 'Bukti bayar perpanjangan wajib diunggah.',
            'payment_proof.uploaded' => 'Bukti bayar perpanjangan gagal diunggah. Pastikan ukurannya maksimal '.$maxSize.'.',
            'payment_proof.image' => 'Bukti bayar perpanjangan harus berupa gambar.',
            'payment_proof.mimes' => 'Bukti bayar perpanjangan harus berformat JPG, JPEG, atau PNG.',
            'payment_proof.max' => 'Ukuran file maksimal '.$maxSize.'.',
        ];
    }

    // function untuk mengambil data sewa yang mau diperpanjang
    public function rental(): Rental
    {
        return $this->route('rental');
    }

    // function untuk mengambil jumlah tambahan hari
    public function additionalDays(): int
    {
        if ($this->filled('additional_days')) {
            return $this->integer('additional_days');
        }

        return app(RentalExtensionService::class)->daysUntil($this->rental(), Carbon::parse($this->input('new_end_date')));
    }
}
