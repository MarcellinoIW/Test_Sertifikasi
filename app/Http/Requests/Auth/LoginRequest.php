<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// request untuk validasi form login
class LoginRequest extends FormRequest
{
    // maksimal percobaan login gagal
    private const MAX_ATTEMPTS = 5;

    // function untuk mengecek user boleh pakai request ini
    public function authorize(): bool
    {
        return true;
    }

    // function untuk aturan validasi form login
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    // function untuk pesan error validasi
    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    // function untuk mengecek apakah login sudah terlalu sering gagal
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

    // function untuk menambah hitungan login gagal
    public function hitRateLimiter(): void
    {
        RateLimiter::hit($this->throttleKey());
    }

    // function untuk mereset hitungan login gagal
    public function clearRateLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    // function untuk membuat key pembatas login (email + IP)
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
