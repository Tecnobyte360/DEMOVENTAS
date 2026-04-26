<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // Importar el trait

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /* ===================== BODEGAS ASIGNADAS ===================== */

    public function bodegas()
    {
        return $this->belongsToMany(\App\Models\Bodega::class, 'bodega_user')->withTimestamps();
    }

    /**
     * Puede ver todas las bodegas si tiene el permiso `bodegas.ver_todas`.
     * Si no, solo las que tenga asignadas via `bodega_user`.
     */
    public function puedeVerTodasLasBodegas(): bool
    {
        return $this->can('bodegas.ver_todas');
    }

    /**
     * IDs de bodegas accesibles para el usuario.
     * Retorna null cuando puede ver todas (sin restriccion).
     */
    public function bodegasPermitidasIds(): ?array
    {
        if ($this->puedeVerTodasLasBodegas()) {
            return null;
        }
        return $this->bodegas()->pluck('bodegas.id')->all();
    }
}
