<?php

namespace App\Models\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MediosPago\MedioPagos;
use App\Models\TurnosCaja\turnos_caja;

class FacturaPago extends Model
{
    protected $table = 'factura_pagos';

    protected $fillable = [
        'factura_id',
        'fecha',
        'metodo',
        'referencia',
        'monto',
        'notas',
        'medio_pago_id',
        'turno_id',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function medioPago(): BelongsTo
    {
        return $this->belongsTo(MedioPagos::class, 'medio_pago_id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(turnos_caja::class, 'turno_id');
    }
}
