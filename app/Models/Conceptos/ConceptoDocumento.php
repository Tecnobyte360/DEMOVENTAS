<?php

namespace App\Models\Conceptos;

use Illuminate\Database\Eloquent\Model;
use App\Models\CuentasContables\PlanCuentas;

class ConceptoDocumento extends Model
{
    protected $table = 'conceptos_documentos';

    protected $fillable = [
        'codigo','nombre','tipo','descripcion','activo',
    ];

    public function scopeActivos($q){ return $q->where('activo', 1); }

    public function cuentas()
    {
        return $this->belongsToMany(
            PlanCuentas::class,
            'concepto_documento_cuenta',
            'concepto_documento_id',
            'plan_cuenta_id'
        )->withPivot(['rol','prioridad'])
         ->orderBy('concepto_documento_cuenta.prioridad', 'asc');
    }
}
