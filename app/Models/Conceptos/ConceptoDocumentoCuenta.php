<?php

namespace App\Models\Conceptos;

use Illuminate\Database\Eloquent\Model;
use App\Models\CuentasContables\PlanCuentas;

class ConceptoDocumentoCuenta extends Model
{
    protected $table = 'concepto_documento_cuenta';

    protected $fillable = [
        'concepto_documento_id','plan_cuenta_id','rol','naturaleza','porcentaje','prioridad'
    ];

  public function concepto()
    {
        return $this->belongsTo(
            ConceptoDocumento::class,
            'concepto_documento_id'
        );
    }

    // ← Aquí sí existe la relación plan()
    public function plan()
    {
        return $this->belongsTo(
            \App\Models\CuentasContables\PlanCuentas::class,
            'plan_cuenta_id'
        );
    }
}
