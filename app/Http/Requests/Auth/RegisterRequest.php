<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// request untuk validasi form register
class RegisterRequest extends FormRequest
{
    // function untuk mengecek user boleh pakai request ini
    public function authorize(): bool
    {
        return true;
    }

    // function untuk aturan validasi form register
    public function rules(): array
    {
        // Akun DITOLAK diabaikan saat cek unik, sehingga NIK/email-nya boleh dipakai daftar ulang.
        $notRejected = fn ($query) => $query->where('status', '!=', UserStatus::Rejected->value);

        return [
            // "bail": berhenti di error pertama, agar pendaftar pending hanya mendapat satu pesan.
            'nik' => ['bail', 'required', 'digits:16', $this->notPending('NIK'), Rule::unique('users', 'nik')->where($notRejected)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits_between:10,15'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', $this->notPending('Email'), Rule::unique('users', 'email')->where($notRejected)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    // function untuk mengecek NIK / email tidak sedang menunggu verifikasi
    private function notPending(string $label): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($label) {
            if (User::query()->where($attribute, $value)->where('status', UserStatus::Pending)->exists()) {
                $fail("{$label} ini sedang menunggu verifikasi admin. Silakan tunggu hasil verifikasi.");
            }
        };
    }

    // function untuk merapikan email sebelum divalidasi
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    // function untuk pesan error validasi
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.digits_between' => 'Nomor telepon harus berupa angka 10 sampai 15 digit.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
