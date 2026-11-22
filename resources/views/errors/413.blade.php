{{--
    Ditampilkan otomatis oleh Laravel untuk PostTooLargeException (HTTP 413), yaitu saat
    data yang dikirim melebihi post_max_size PHP. Exception ini terjadi sebelum session
    berjalan, sehingga halaman ini sengaja tidak memakai layout (yang membutuhkan session/auth).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>File Terlalu Besar · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <main class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body p-4">
                        <i class="bi bi-file-earmark-x text-danger display-5"></i>
                        <h1 class="h4 mt-3">File terlalu besar</h1>
                        <p class="text-muted mb-4">
                            Ukuran file yang diunggah melebihi batas. Gunakan gambar JPG/PNG
                            berukuran <strong>maksimal {{ str_replace('.', ',', config('garasi.max_upload_kb') / 1024) }}MB</strong>, lalu kirim ulang formulir.
                        </p>
                        @php $previous = request()->headers->get('referer'); @endphp
                        <a href="{{ $previous ?: url('/') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke formulir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
