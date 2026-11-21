<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tab status pada halaman verifikasi penyewa.
 */
class TenantFilterRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(UserStatus::class)],
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
            'status.enum' => 'Status penyewa tidak valid.',
        ];
    }

    /**
     * Status yang dipilih; default pending.
     *
     * @return UserStatus
     */
    public function tenantStatus(): UserStatus
    {
        return $this->enum('status', UserStatus::class) ?? UserStatus::Pending;
    }
}
