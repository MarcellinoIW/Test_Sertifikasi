<?php

namespace App\Http\Requests\Admin;

use App\Enums\GarageType;
use App\Services\GarageCodeGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi form pembuatan garasi secara batch.
 */
class StoreGarageBatchRequest extends FormRequest
{
    /**
     * Hanya admin (sudah dijamin middleware role:admin).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Aturan validasi.
     *
     * @return array<string, array<int, mixed>>
     */
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

    /**
     * Validasi tambahan: nomor terakhir + jumlah tidak boleh melebihi 999.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $type = GarageType::tryFrom((string) $this->input('type'));
                if ($type === null || $validator->errors()->has('quantity')) {
                    return;
                }

                $remaining = app(GarageCodeGenerator::class)->remaining($type);
                if ($this->integer('quantity') > $remaining) {
                    $validator->errors()->add(
                        'quantity',
                        "Jumlah melebihi batas kode. Sisa kuota garasi {$type->label()} hanya {$remaining} garasi (maksimal {$type->prefix()}999).",
                    );
                }
            },
        ];
    }

    /**
     * Pesan error berbahasa Indonesia.
     *
     * @return array<string, string>
     */
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

    /**
     * Tipe garasi tervalidasi sebagai enum.
     *
     * @return GarageType
     */
    public function garageType(): GarageType
    {
        return $this->enum('type', GarageType::class);
    }

    /**
     * Harga per kWh tervalidasi; null untuk tipe selain electric.
     *
     * @return float|null
     */
    public function electricityPrice(): ?float
    {
        return $this->garageType()->hasCharging() ? $this->float('electricity_price_per_kwh') : null;
    }
}
