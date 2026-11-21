@php
    /*
     * Menu navbar per role. Item hanya tampil jika route-nya sudah terdaftar,
     * jadi menu otomatis bertambah seiring fitur dibuat.
     * Format: [route, pola routeIs untuk status aktif, ikon, label]
     */
    $user = auth()->user();
    $menus = match (true) {
        $user?->isAdmin() => [
            ['admin.dashboard', 'admin.dashboard', 'speedometer2', 'Dashboard'],
            ['admin.garages.index', 'admin.garages.*', 'house-door', 'Garasi'],
            ['admin.tenants.index', 'admin.tenants.*', 'person-check', 'Verifikasi Penyewa'],
            ['admin.extensions.index', 'admin.extensions.*', 'calendar-plus', 'Perpanjang Sewa'],
            ['admin.reports.index', 'admin.reports.*', 'bar-chart-line', 'Laporan'],
        ],
        $user?->isTenant() => [
            ['tenant.dashboard', 'tenant.dashboard', 'speedometer2', 'Dashboard'],
            ['tenant.rentals.create', 'tenant.rentals.create', 'plus-circle', 'Sewa Baru'],
            ['tenant.rentals.index', 'tenant.rentals.index', 'card-list', 'Sewa Saya'],
        ],
        default => [],
    };
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('home') }}">
            <i class="bi bi-car-front-fill me-1"></i> {{ config('app.name') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                @foreach ($menus as [$route, $pattern, $icon, $label])
                    @if (Route::has($route))
                        <li class="nav-item">
                            <a @class(['nav-link', 'active' => request()->routeIs($pattern)]) href="{{ route($route) }}">
                                <i class="bi bi-{{ $icon }} me-1"></i>{{ $label }}
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>

            <ul class="navbar-nav">
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>{{ $user->name }}
                            <span class="badge bg-light text-primary ms-1">{{ $user->role->label() }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">{{ $user->email }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-1"></i>Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs('login')]) href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs('register')]) href="{{ route('register') }}">
                            <i class="bi bi-person-plus me-1"></i>Daftar
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
