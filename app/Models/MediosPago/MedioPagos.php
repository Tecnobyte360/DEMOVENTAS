<?php

namespace App\Models\MediosPago;

use Illuminate\Database\Eloquent\Model;

class MedioPagos extends Model
{
    protected $table = 'medio_pagos';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];

    /**
     * Relación 1–1: cuenta contable asociada al medio de pago.
     * (tabla: medio_pago_cuentas, FK: medio_pago_id)
     */
    public function cuenta()
    {
        return $this->hasOne(MedioPagoCuenta::class, 'medio_pago_id');
    }

    /* Scopes útiles (opcionales) */
    public function scopeActivos($q, bool $solo = true)
    {
        return $solo ? $q->where('activo', true) : $q;
    }

    public function scopeOrdenados($q)
    {
        return $q->orderBy('orden')->orderBy('id');
    }
    
}
