<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form registrasi penyewa.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Semua tamu boleh mendaftar.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi: NIK 16 digit unik, telepon 10-15 digit,
     * email unik, password minimal 8 karakter dengan konfirmasi.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'digits_between:10,15'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
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
