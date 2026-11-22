<?php

namespace App\Http\Requests\Tenant;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Vehicle;
use App\Services\RentalPriceCalculator;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// request untuk validasi form sewa garasi
class StoreRentalRequest extends FormRequest
{
    // function untuk mengecek user boleh pakai request ini
    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    // function untuk aturan validasi form sewa garasi
    public function rules(): array
    {
        $vehicleType = $this->vehicleType();
        $calculator = app(RentalPriceCalculator::class);
        $rentalService = app(RentalService::class);

        return [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$rentalService->earliestStartDate()->toDateString(),
                'before_or_equal:'.$rentalService->latestStartDate()->toDateString(),
            ],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'plate_number' => [
                Rule::requiredIf(fn () => $vehicleType?->requiresPlate() ?? false),
                'nullable',
                'string',
                'max:15',
                'regex:'.Vehicle::PLATE_PATTERN,
            ],
            'brand_model' => ['required', 'string', 'max:100'],
            'battery_remaining_kwh' => [
                Rule::requiredIf(fn () => $vehicleType?->isElectric() ?? false),
                'nullable',
                'numeric',
                'min:0',
                'max:'.$calculator->batteryCapacityKwh(),
            ],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            // Jumlah hari hanya divalidasi untuk sewa harian, dengan batas mengikuti
            // BULAN TANGGAL MULAI. Untuk bulanan field ini (disabled di form) dikecualikan;
            // jumlah hari = jumlah hari bulan tanggal mulai, dihitung RentalPriceCalculator.
            'days' => [
                Rule::excludeIf(fn () => $this->input('rental_type') === RentalType::Monthly->value),
                'required',
                'integer',
                'min:1',
                'max:'.$calculator->maxDailyDays($this->startDateOrToday()),
            ],
            'garage_type' => ['required', Rule::enum(GarageType::class)],
            'garage_id' => ['required', 'integer', Rule::exists('garages', 'id')],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:'.config('garasi.max_upload_kb')],
        ];
    }

    // function untuk validasi tambahan (cek kendaraan cocok dengan tipe garasi)
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $vehicleType = $this->vehicleType();
                $garageType = GarageType::tryFrom((string) $this->input('garage_type'));

                if ($vehicleType === null || $garageType === null) {
                    return;
                }

                if (! $vehicleType->canUseGarage($garageType)) {
                    $validator->errors()->add(
                        'garage_type',
                        "Garasi {$garageType->label()} tidak dapat digunakan untuk {$vehicleType->label()}.",
                    );

                    return;
                }

                $garage = Garage::query()->find($this->input('garage_id'));
                if ($garage !== null && $garage->type !== $garageType) {
                    $validator->errors()->add('garage_id', 'Garasi yang dipilih tidak sesuai dengan tipe garasi.');
                }
            },
        ];
    }

    // function untuk pesan error validasi
    public function messages(): array
    {
        // Label batas upload dari config, misal 5120 KB -> "5MB".
        $maxSize = str_replace('.', ',', (string) (config('garasi.max_upload_kb') / 1024)).'MB';

        return [
            'vehicle_type.required' => 'Tipe kendaraan wajib dipilih.',
            'vehicle_type.enum' => 'Tipe kendaraan tidak valid.',
            'plate_number.required' => 'Nomor plat wajib diisi untuk kendaraan selain sepeda.',
            'plate_number.max' => 'Nomor plat maksimal 15 karakter.',
            'plate_number.regex' => 'Format nomor plat tidak valid. Contoh: B 1234 CD.',
            'brand_model.required' => 'Merek & tipe kendaraan wajib diisi.',
            'brand_model.max' => 'Merek & tipe kendaraan maksimal 100 karakter.',
            'battery_remaining_kwh.required' => 'Sisa baterai wajib diisi untuk mobil listrik.',
            'battery_remaining_kwh.numeric' => 'Sisa baterai harus berupa angka.',
            'battery_remaining_kwh.min' => 'Sisa baterai minimal :min kWh.',
            'battery_remaining_kwh.max' => 'Sisa baterai maksimal :max kWh.',
            'rental_type.required' => 'Tipe sewa wajib dipilih.',
            'rental_type.enum' => 'Tipe sewa tidak valid.',
            'days.required' => 'Jumlah hari wajib diisi untuk sewa harian.',
            'days.integer' => 'Jumlah hari harus berupa bilangan bulat.',
            'days.min' => 'Jumlah hari minimal :min hari.',
            'days.max' => 'Jumlah hari sewa harian maksimal :max hari untuk bulan tanggal mulai.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date_format' => 'Format tanggal mulai tidak valid.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'start_date.before_or_equal' => 'Tanggal mulai paling lambat '.config('garasi.max_start_days_ahead').' hari dari hari ini.',
            'garage_type.required' => 'Tipe garasi wajib dipilih.',
            'garage_type.enum' => 'Tipe garasi tidak valid.',
            'garage_id.required' => 'Silakan pilih garasi.',
            'garage_id.exists' => 'Garasi yang dipilih tidak ditemukan.',
            'payment_proof.required' => 'Bukti pelunasan wajib diunggah.',
            'payment_proof.uploaded' => 'Bukti pelunasan gagal diunggah. Pastikan ukurannya maksimal '.$maxSize.'.',
            'payment_proof.image' => 'Bukti pelunasan harus berupa gambar.',
            'payment_proof.mimes' => 'Bukti pelunasan harus berformat JPG, JPEG, atau PNG.',
            'payment_proof.max' => 'Ukuran file maksimal '.$maxSize.'.',
        ];
    }

    // function untuk mengambil tipe kendaraan yang dipilih
    public function vehicleType(): ?VehicleType
    {
        return VehicleType::tryFrom((string) $this->input('vehicle_type'));
    }

    // function untuk mengambil tanggal mulai sewa (default hari ini)
    public function startDateOrToday(): CarbonImmutable
    {
        $value = (string) $this->input('start_date');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? CarbonImmutable::createFromFormat('!Y-m-d', $value) : false;

        return $date ?: CarbonImmutable::today();
    }
}
