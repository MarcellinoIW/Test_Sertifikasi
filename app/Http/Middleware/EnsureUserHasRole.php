<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses route berdasarkan role pengguna.
 *
 * Pemakaian: ->middleware('role:admin') atau ->middleware('role:tenant').
 * Penyewa yang statusnya tidak lagi approved akan otomatis di-logout.
 */
class EnsureUserHasRole
{
    /**
     * Tangani request yang masuk.
     *
     * @param  Request  $request  Request saat ini.
     * @param  Closure(Request): Response  $next  Middleware berikutnya.
     * @param  string  $role  Role yang diizinkan (nilai enum UserRole).
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        /** @var User|null $user */
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
