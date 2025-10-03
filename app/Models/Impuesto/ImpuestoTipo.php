<?php

namespace App\Models\Impuesto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Impuestos\Impuesto;

class ImpuestoTipo extends Model
{
    protected $table = 'impuesto_tipos';

    protected $fillable = ['codigo','nombre','es_retencion','activo','orden'];

    protected $casts = [
        'es_retencion' => 'boolean',
        'activo'       => 'boolean',
        'orden'        => 'integer',
    ];

    public function impuestos(): HasMany
    {
        return $this->hasMany(Impuesto::class, 'tipo_id');
    }

    public function scopeActivos($q){ return $q->where('activo', true); }
}
