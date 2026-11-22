<?php

namespace App\Http\Requests\Admin;

use App\Enums\GarageType;
use App\Services\GarageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// request untuk validasi form tambah garasi
class StoreGarageBatchRequest extends FormRequest
{
    // function untuk mengecek user boleh pakai request ini
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    // function untuk aturan validasi form tambah garasi
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(GarageType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('garasi.max_garages_per_batch')],
            'daily_price' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'electricity_price_per_kwh' => [
                Rule::requiredIf(fn () => $this->input('type') === GarageType::Electric->value),
                'nullable',
                'numeric',
                'min:1',
                'max:9999999999',
            ],
        ];
    }

    // function untuk validasi tambahan (cek sisa kuota nomor garasi)
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $type = GarageType::tryFrom((string) $this->input('type'));
                if ($type === null || $validator->errors()->has('quantity')) {
                    return;
                }

                $remaining = app(GarageService::class)->remaining($type);
                if ($this->integer('quantity') > $remaining) {
                    $validator->errors()->add(
                        'quantity',
                        "Jumlah melebihi batas kode. Sisa kuota garasi {$type->label()} hanya {$remaining} garasi (maksimal {$type->prefix()}999).",
                    );
                }
            },
        ];
    }

    // function untuk pesan error validasi
    public function messages(): array
    {
        return [
            'type.required' => 'Tipe garasi wajib dipilih.',
            'type.enum' => 'Tipe garasi tidak valid.',
            'quantity.required' => 'Jumlah garasi wajib diisi.',
            'quantity.integer' => 'Jumlah garasi harus berupa bilangan bulat.',
            'quantity.min' => 'Jumlah garasi minimal 1.',
            'quantity.max' => 'Jumlah garasi maksimal :max.',
            'daily_price.required' => 'Harga sewa harian wajib diisi.',
            'daily_price.numeric' => 'Harga sewa harian harus berupa angka.',
            'daily_price.min' => 'Harga sewa harian minimal Rp 1.',
            'daily_price.max' => 'Harga sewa harian terlalu besar.',
            'electricity_price_per_kwh.required' => 'Harga isi listrik per kWh wajib diisi untuk garasi mobil listrik.',
            'electricity_price_per_kwh.numeric' => 'Harga isi listrik per kWh harus berupa angka.',
            'electricity_price_per_kwh.min' => 'Harga isi listrik per kWh minimal Rp 1.',
            'electricity_price_per_kwh.max' => 'Harga isi listrik per kWh terlalu besar.',
        ];
    }

    // function untuk mengambil tipe garasi yang dipilih
    public function garageType(): GarageType
    {
        return $this->enum('type', GarageType::class);
    }

    // function untuk mengambil harga listrik per kWh (khusus garasi listrik)
    public function electricityPrice(): ?float
    {
        return $this->garageType()->hasCharging() ? $this->float('electricity_price_per_kwh') : null;
    }
}
