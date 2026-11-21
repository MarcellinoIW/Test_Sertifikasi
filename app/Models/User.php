<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Pengguna aplikasi: admin (dibuat via seeder) atau penyewa (registrasi mandiri).
 *
 * @property int $id
 * @property string|null $nik
 * @property string $name
 * @property string|null $phone
 * @property string $email
 * @property UserRole $role
 * @property UserStatus $status
 * @property Carbon $created_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atribut yang boleh diisi secara massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nik',
        'name',
        'phone',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Konversi tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Kendaraan yang pernah didaftarkan penyewa.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Seluruh sewa milik penyewa.
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Perpanjangan sewa yang diproses oleh admin ini.
     *
     * @return HasMany<RentalExtension, $this>
     */
    public function processedExtensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class, 'admin_id');
    }

    /**
     * Apakah pengguna ini admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Apakah pengguna ini penyewa.
     *
     * @return bool
     */
    public function isTenant(): bool
    {
        return $this->role === UserRole::Tenant;
    }

    /**
     * Apakah akun sudah disetujui sehingga boleh login.
     * Admin selalu dianggap disetujui.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->isAdmin() || $this->status === UserStatus::Approved;
    }

    /**
     * Nama route halaman awal (dashboard) sesuai role pengguna.
     *
     * @return string
     */
    public function homeRoute(): string
    {
        return $this->isAdmin() ? 'admin.dashboard' : 'tenant.dashboard';
    }

    /**
     * Scope: hanya pengguna dengan role penyewa.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('role', UserRole::Tenant);
    }

    /**
     * Scope: filter berdasarkan status verifikasi.
     *
     * @param  Builder<User>  $query
     * @param  UserStatus  $status  Status yang dicari.
     * @return Builder<User>
     */
    public function scopeWithStatus(Builder $query, UserStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
