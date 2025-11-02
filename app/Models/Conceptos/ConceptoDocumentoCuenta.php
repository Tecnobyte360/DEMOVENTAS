<?php

namespace App\Models\Conceptos;

use Illuminate\Database\Eloquent\Model;
use App\Models\CuentasContables\PlanCuentas;

class ConceptoDocumentoCuenta extends Model
{
    protected $table = 'concepto_documento_cuenta';

    protected $fillable = [
        'concepto_documento_id',
        'plan_cuenta_id',
        'rol',
        'naturaleza',   // 'debito' | 'credito' | null
        'porcentaje',
        'prioridad',
    ];

    protected $casts = [
        'porcentaje' => 'float',
        'prioridad'  => 'int',
    ];

    public function concepto()
    {
        return $this->belongsTo(ConceptoDocumento::class, 'concepto_documento_id');
    }

    public function cuenta()
    {
        return $this->belongsTo(PlanCuentas::class, 'plan_cuenta_id');
    }
}
