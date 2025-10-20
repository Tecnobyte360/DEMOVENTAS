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

        // Config dinámica:
        'requiere_turno',
        'crear_movimiento',
        'tipo_movimiento',
        'contar_en_total',
        'clave_turno',
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'orden'            => 'integer',
        'requiere_turno'   => 'boolean',
        'crear_movimiento' => 'boolean',
        'contar_en_total'  => 'boolean',
    ];

    /** Relación 1–1: cuenta contable asociada */
    public function cuenta()
    {
        return $this->hasOne(MedioPagoCuenta::class, 'medio_pago_id');
    }

    /* Scopes */
    public function scopeActivos($q, bool $solo = true)
    {
        return $solo ? $q->where('activo', true) : $q;
    }

    public function scopeOrdenados($q)
    {
        return $q->orderBy('orden')->orderBy('id');
    }
}
