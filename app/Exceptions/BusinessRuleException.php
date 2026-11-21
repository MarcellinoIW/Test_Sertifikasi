<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Pelanggaran aturan bisnis yang terdeteksi di service (misal garasi sudah
 * terpakai, kode garasi melebihi 999). Otomatis dirender sebagai redirect
 * kembali dengan pesan error, sehingga controller tetap tipis.
 */
class BusinessRuleException extends RuntimeException
{
    /**
     * @param  string  $message  Pesan error untuk pengguna (Bahasa Indonesia).
     * @param  string|null  $field  Nama field form terkait; null untuk pesan umum (flash).
     */
    public function __construct(string $message, private readonly ?string $field = null)
    {
        parent::__construct($message);
    }

    /**
     * Nama field form yang terkait dengan error.
     *
     * @return string|null
     */
    public function field(): ?string
    {
        return $this->field;
    }

    /**
     * Render exception menjadi response HTTP.
     *
     * @param  Request  $request  Request saat ini.
     * @return RedirectResponse|JsonResponse
     */
    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'errors' => $this->field ? [$this->field => [$this->getMessage()]] : [],
            ], 422);
        }

        $redirect = back()->withInput($request->except(['password', 'password_confirmation']));

        return $this->field
            ? $redirect->withErrors([$this->field => $this->getMessage()])
            : $redirect->with('error', $this->getMessage());
    }
}
