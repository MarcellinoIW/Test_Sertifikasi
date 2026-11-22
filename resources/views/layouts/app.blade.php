<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f5f6f8; }
        .stat-card .bi { font-size: 2rem; opacity: .75; }
        .table > :not(caption) > * > * { vertical-align: middle; }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    @include('layouts.partials.navbar')

    <main class="container py-4 flex-grow-1">
        @hasSection('header')
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <h1 class="h3 mb-0">@yield('header')</h1>
                <div>@yield('actions')</div>
            </div>
        @endif

        @include('layouts.partials.flash')

        @yield('content')
    </main>

    <footer class="border-top bg-white py-3 small text-muted">
        <div class="container">
            <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cek ukuran file di browser untuk input bertanda data-max-size (byte), agar file
        // yang terlalu besar tidak sempat dikirim. Validasi server tetap berlaku.
        (() => {
            const inputs = document.querySelectorAll('input[type="file"][data-max-size]');

            const check = (input) => {
                const max = Number(input.dataset.maxSize);
                const file = input.files[0];
                const tooBig = file && file.size > max;
                let feedback = input.parentElement.querySelector('.js-file-size-feedback');

                input.classList.toggle('is-invalid', Boolean(tooBig));
                if (tooBig) {
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback js-file-size-feedback';
                        input.insertAdjacentElement('afterend', feedback);
                    }
                    const sizeMb = (file.size / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 });
                    const maxMb = (max / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 });
                    feedback.textContent = `Ukuran file ${sizeMb} MB melebihi batas maksimal ${maxMb} MB.`;
                } else if (feedback) {
                    feedback.remove();
                }

                return !tooBig;
            };

            inputs.forEach((input) => {
                input.addEventListener('change', () => check(input));
                input.form?.addEventListener('submit', (event) => {
                    if (!check(input)) {
                        event.preventDefault();
                        input.focus();
                    }
                });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
