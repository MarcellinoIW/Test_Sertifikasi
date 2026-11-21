<?php

namespace App\Providers;

use App\Services\RentalPriceCalculator;
use App\Support\Formatter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider utama aplikasi.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan service ke container. Kalkulator harga dibuat dengan
     * konstanta bisnis dari config/garasi.php.
     */
    public function register(): void
    {
        $this->app->singleton(RentalPriceCalculator::class, fn () => new RentalPriceCalculator(
            monthlyMultiplier: (int) config('garasi.monthly_multiplier'),
            batteryCapacityKwh: (float) config('garasi.battery_capacity_kwh'),
        ));
    }

    /**
     * Bootstrap aplikasi: paginasi bergaya Bootstrap 5 dan directive Blade
     * untuk format tampilan.
     *
     * - @rupiah($nominal) -> "Rp 1.250.000"
     * - @tanggal($date)   -> "21 Nov 2026"
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Blade::directive('rupiah', fn (string $expression) => '<?php echo e('.Formatter::class."::rupiah({$expression})); ?>");
        Blade::directive('tanggal', fn (string $expression) => '<?php echo e('.Formatter::class."::date({$expression})); ?>");
    }
}
