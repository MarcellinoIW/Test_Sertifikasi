@extends('layouts.app')

@section('title', 'Daftar Penyewa')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus-fill text-primary display-5"></i>
                        <h1 class="h4 mt-2 mb-0">Daftar Sebagai Penyewa</h1>
                        <p class="text-muted small mb-0">
                            Akun aktif setelah diverifikasi admin. Siapkan KTP untuk verifikasi.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('register.store') }}" novalidate>
                        @csrf

                        <x-form.input name="nik" label="NIK" inputmode="numeric" maxlength="16" required autofocus
                                      help="16 digit angka sesuai KTP." />
                        <x-form.input name="name" label="Nama Lengkap" required autocomplete="name" />

                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="phone" label="No. Telepon" type="tel" inputmode="numeric" maxlength="15" required
                                              help="10–15 digit angka." autocomplete="tel" />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="email" label="Email" type="email" required autocomplete="email" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <x-form.input name="password" label="Password" type="password" required
                                              help="Minimal 8 karakter." autocomplete="new-password" />
                            </div>
                            <div class="col-sm-6">
                                <x-form.input name="password_confirmation" label="Konfirmasi Password" type="password" required
                                              autocomplete="new-password" />
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i> Daftar
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-white text-center small py-3">
                    Sudah punya akun? <a href="{{ route('login') }}">Masuk</a>
                </div>
            </div>
        </div>
    </div>
@endsection
