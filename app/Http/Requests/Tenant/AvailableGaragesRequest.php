<?php

namespace App\Http\Requests\Tenant;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi parameter endpoint JSON daftar garasi tersedia.
 */
class AvailableGaragesRequest extends FormRequest
{
    /**
     * Hanya penyewa (sudah dijamin middleware role:tenant).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    /**
     * Aturan validasi.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'garage_type' => ['required', Rule::enum(GarageType::class)],
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
            'vehicle_type.required' => 'Tipe kendaraan wajib dipilih.',
            'vehicle_type.enum' => 'Tipe kendaraan tidak valid.',
            'garage_type.required' => 'Tipe garasi wajib dipilih.',
            'garage_type.enum' => 'Tipe garasi tidak valid.',
        ];
    }
}
