<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// controller untuk halaman awal (/)
class HomeController extends Controller
{
    // function untuk mengarahkan user ke halaman sesuai role
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        return $user
            ? redirect()->route($user->homeRoute())
            : redirect()->route('login');
    }
}
