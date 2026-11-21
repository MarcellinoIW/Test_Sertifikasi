<?php

namespace App\Http\Requests\Admin;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi filter (query string) pada daftar garasi.
 */
class GarageFilterRequest extends FormRequest
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
            'type' => ['nullable', Rule::enum(GarageType::class)],
            'status' => ['nullable', Rule::enum(GarageStatus::class)],
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
            'type.enum' => 'Filter tipe garasi tidak valid.',
            'status.enum' => 'Filter status garasi tidak valid.',
        ];
    }

    /**
     * Filter tipe garasi, atau null jika tidak dipilih.
     *
     * @return GarageType|null
     */
    public function garageType(): ?GarageType
    {
        return $this->enum('type', GarageType::class);
    }

    /**
     * Filter status garasi, atau null jika tidak dipilih.
     *
     * @return GarageStatus|null
     */
    public function garageStatus(): ?GarageStatus
    {
        return $this->enum('status', GarageStatus::class);
    }
}
