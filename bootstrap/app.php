<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Pengguna yang sudah login dan membuka halaman tamu (login/register)
        // diarahkan ke dashboard sesuai role-nya.
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pelanggaran aturan bisnis adalah validasi biasa (sudah ditampilkan ke
        // pengguna), bukan error sistem, jadi tidak perlu dicatat di laravel.log.
        $exceptions->dontReport(BusinessRuleException::class);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
