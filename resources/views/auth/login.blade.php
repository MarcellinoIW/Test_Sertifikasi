@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-car-front-fill text-primary display-5"></i>
                        <h1 class="h4 mt-2 mb-0">Masuk ke {{ config('app.name') }}</h1>
                        <p class="text-muted small mb-0">Gunakan email dan password akun Anda.</p>
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" novalidate>
                        @csrf

                        <x-form.input name="email" label="Email" type="email" required autofocus autocomplete="email" />
                        <x-form.input name="password" label="Password" type="password" required autocomplete="current-password" />

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" @checked(old('remember'))>
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-white text-center small py-3">
                    Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai penyewa</a>
                </div>
            </div>
        </div>
    </div>
@endsection
