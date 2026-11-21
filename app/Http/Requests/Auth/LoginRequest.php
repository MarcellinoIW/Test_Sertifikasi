<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validasi form login beserta pembatasan percobaan (rate limit).
 */
class LoginRequest extends FormRequest
{
    /**
     * Maksimal percobaan login gagal sebelum dikunci sementara.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Semua tamu boleh mengirim form login.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
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
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Pastikan IP + email ini belum melewati batas percobaan login.
     *
     * @throws ValidationException Jika terlalu banyak percobaan.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Catat satu percobaan login yang gagal.
     */
    public function hitRateLimiter(): void
    {
        RateLimiter::hit($this->throttleKey());
    }

    /**
     * Reset penghitung percobaan setelah login berhasil.
     */
    public function clearRateLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Kunci rate limit berdasarkan email dan IP.
     *
     * @return string
     */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
