<?php

namespace App\Models\Conceptos;

use Illuminate\Database\Eloquent\Model;
use App\Models\CuentasContables\PlanCuentas;

class ConceptoDocumento extends Model
{
    protected $table = 'conceptos_documentos';
    protected $fillable = ['codigo','nombre','tipo','descripcion','activo'];

    // ← Esta relación debe devolver la PIVOTE, no PlanCuentas
    public function cuentas()
    {
        return $this->hasMany(
            ConceptoDocumentoCuenta::class,
            'concepto_documento_id'
        );
    }

    // (opcional) scopes
    public function scopeActivos($q)  { return $q->where('activo', 1); }
    public function scopeEntradas($q) { return $q->where('tipo', 'entrada'); }
}