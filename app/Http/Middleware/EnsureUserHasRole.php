<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// middleware untuk membatasi halaman sesuai role user
class EnsureUserHasRole
{
    // function untuk mengecek role user sebelum masuk halaman
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->isApproved()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akun Anda belum disetujui admin.');
        }

        abort_unless($user->role === UserRole::from($role), Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
