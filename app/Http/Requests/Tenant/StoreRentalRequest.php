<?php

namespace App\Http\Requests\Tenant;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Services\RentalPriceCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi form sewa garasi baru. Aturan bisnis yang bergantung pada
 * kondisi DB terkini (garasi masih tersedia, plat tidak dipakai) dicek
 * ulang di RentalService di dalam transaksi.
 */
class StoreRentalRequest extends FormRequest
{
    /**
     * Pola nomor plat Indonesia, misal "B 1234 CD", "D 12 A", "AB 1234".
     */
    private const PLATE_PATTERN = '/^[A-Za-z]{1,2}\s*\d{1,4}(\s*[A-Za-z]{1,3})?$/';

    /**
     * Hanya penyewa (sudah dijamin middleware role:tenant).
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    /**
     * Aturan validasi.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $vehicleType = $this->vehicleType();
        $calculator = app(RentalPriceCalculator::class);

        return [
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'plate_number' => [
                Rule::requiredIf(fn () => $vehicleType?->requiresPlate() ?? false),
                'nullable',
                'string',
                'max:15',
                'regex:'.self::PLATE_PATTERN,
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
            // Jumlah hari hanya divalidasi untuk sewa harian. Untuk bulanan field ini
            // dikecualikan dari data tervalidasi; jumlah hari = jumlah hari bulan berjalan
            // dan dihitung oleh RentalPriceCalculator.
            'days' => [
                Rule::excludeIf(fn () => $this->input('rental_type') === RentalType::Monthly->value),
                'required',
                'integer',
                'min:1',
                'max:'.$calculator->maxDailyDays(today()),
            ],
            'garage_type' => ['required', Rule::enum(GarageType::class)],
            'garage_id' => ['required', 'integer', Rule::exists('garages', 'id')],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
     * Validasi tambahan: kompatibilitas tipe kendaraan, tipe garasi, dan garasi yang dipilih.
     *
     * @return array<int, callable>
     */
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

    /**
     * Pesan error berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
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
            'days.max' => 'Jumlah hari sewa harian maksimal :max hari untuk bulan ini.',
            'garage_type.required' => 'Tipe garasi wajib dipilih.',
            'garage_type.enum' => 'Tipe garasi tidak valid.',
            'garage_id.required' => 'Silakan pilih garasi.',
            'garage_id.exists' => 'Garasi yang dipilih tidak ditemukan.',
            'payment_proof.required' => 'Bukti pelunasan wajib diunggah.',
            'payment_proof.image' => 'Bukti pelunasan harus berupa gambar.',
            'payment_proof.mimes' => 'Bukti pelunasan harus berformat JPG, JPEG, atau PNG.',
            'payment_proof.max' => 'Ukuran bukti pelunasan maksimal 2 MB.',
        ];
    }

    /**
     * Tipe kendaraan dari input (null jika belum valid).
     *
     * @return VehicleType|null
     */
    public function vehicleType(): ?VehicleType
    {
        return VehicleType::tryFrom((string) $this->input('vehicle_type'));
    }
}
