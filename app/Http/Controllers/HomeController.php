<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman root: arahkan ke dashboard sesuai role, atau ke login untuk tamu.
 */
class HomeController extends Controller
{
    /**
     * Tangani request ke "/".
     *
     * @param  Request  $request  Request saat ini.
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        return $user
            ? redirect()->route($user->homeRoute())
            : redirect()->route('login');
    }
}
