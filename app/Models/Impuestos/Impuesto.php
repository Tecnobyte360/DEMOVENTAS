<?php

namespace App\Models\Impuestos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Impuesto\ImpuestoTipo;
use App\Models\CuentasContables\PlanCuentas;

class Impuesto extends Model
{
    protected $table = 'impuestos';

    protected $fillable = [
        'codigo','nombre','tipo_id','aplica_sobre',
        'porcentaje','monto_fijo','incluido_en_precio','regla_redondeo',
        'vigente_desde','vigente_hasta','activo','prioridad',
        'cuenta_id','contracuenta_id',
    ];

protected $casts = [
  'activo'             => 'boolean',
  'incluido_en_precio' => 'boolean',
  'porcentaje'         => 'decimal:3',
  'monto_fijo'         => 'decimal:2',
  'vigente_desde'      => 'date',
  'vigente_hasta'      => 'date',
];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(ImpuestoTipo::class, 'tipo_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(PlanCuentas::class, 'cuenta_id');
    }

    public function contracuenta(): BelongsTo
    {
        return $this->belongsTo(PlanCuentas::class, 'contracuenta_id');
    }

    public function scopeActivos($q){ return $q->where('activo', true); }

    public function getLabelValorAttribute(): string
    {
        return $this->porcentaje !== null
            ? number_format($this->porcentaje, 2).' %'
            : '$'.number_format($this->monto_fijo, 2);
    }



}
