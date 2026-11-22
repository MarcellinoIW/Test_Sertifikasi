<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// model untuk data user (admin dan penyewa)
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // kolom yang boleh diisi
    protected $fillable = [
        'nik',
        'name',
        'phone',
        'email',
        'password',
        'role',
        'status',
    ];

    // kolom yang disembunyikan
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // function untuk mengatur tipe data kolom
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    // function relasi user ke kendaraan
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    // function relasi user ke data sewa
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    // function relasi user ke riwayat verifikasi
    public function verifications(): HasMany
    {
        return $this->hasMany(TenantVerification::class)->orderBy('created_at')->orderBy('id');
    }

    // function relasi admin ke perpanjangan yang diproses
    public function processedExtensions(): HasMany
    {
        return $this->hasMany(RentalExtension::class, 'admin_id');
    }

    // function untuk mengecek user adalah admin
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    // function untuk mengecek user adalah penyewa
    public function isTenant(): bool
    {
        return $this->role === UserRole::Tenant;
    }

    // function untuk mengecek akun sudah disetujui
    public function isApproved(): bool
    {
        return $this->isAdmin() || $this->status === UserStatus::Approved;
    }

    // function untuk menentukan halaman dashboard sesuai role
    public function homeRoute(): string
    {
        return $this->isAdmin() ? 'admin.dashboard' : 'tenant.dashboard';
    }

    // function untuk filter user yang role-nya penyewa
    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('role', UserRole::Tenant);
    }

    // function untuk filter user berdasarkan status verifikasi
    public function scopeWithStatus(Builder $query, UserStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
