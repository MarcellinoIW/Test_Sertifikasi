<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

// provider utama aplikasi
class AppServiceProvider extends ServiceProvider
{
    // function untuk mengatur pagination bootstrap dan directive @rupiah / @tanggal
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Blade::directive('rupiah', fn (string $expression) => "<?php echo e('Rp '.number_format((float) ({$expression}), 0, ',', '.')); ?>");
        Blade::directive('tanggal', fn (string $expression) => "<?php echo e(blank(\$__tanggal = ({$expression})) ? '' : \Illuminate\Support\Carbon::parse(\$__tanggal)->translatedFormat('d M Y')); ?>");
    }
}
