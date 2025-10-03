<?php

namespace App\Models\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MediosPago\MedioPagos;

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
}
