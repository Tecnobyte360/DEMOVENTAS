<?php

namespace App\Models\Conceptos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\CuentasContables\PlanCuentas;

class ConceptoDocumento extends Model
{
    protected $table = 'conceptos_documentos';

    protected $fillable = [
        'codigo', 'nombre', 'tipo', 'descripcion', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /* Scopes */
    public function scopeActivos($q)   { return $q->where('activo', true); }
    public function scopeDeTipo($q,$t){ return $q->where('tipo', $t); }

    /* Relación con PUC (PlanCuentas) vía pivote concepto_documento_cuenta */
    public function cuentas()
{
    return $this->belongsToMany(
        \App\Models\CuentasContables\PlanCuentas::class,
        'concepto_documento_cuenta',
        'concepto_documento_id',
        'plan_cuenta_id'
    )
    ->withPivot(['rol','naturaleza','porcentaje','prioridad'])
    ->withTimestamps();
}
}
