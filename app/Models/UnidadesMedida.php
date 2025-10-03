<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadesMedida extends Model
{
   protected $table = 'unidades_medida';

    protected $fillable = ['codigo','nombre','simbolo','tipo','activo'];

    protected $casts = ['activo' => 'boolean'];

    // Normalizaciones
    public function setCodigoAttribute($v){ $this->attributes['codigo'] = strtoupper(trim($v)); }
    public function setSimboloAttribute($v){ $this->attributes['simbolo'] = $v ? trim($v) : null; }
    public function setTipoAttribute($v){ $this->attributes['tipo'] = $v ? strtolower(trim($v)) : null; }

    // Scopes
    public function scopeActivas($q){ return $q->where('activo', true); }
    public function scopeBuscar($q, ?string $t){
        if(!$t) return $q;
        $t = trim($t);
        return $q->where(fn($qq) => $qq
            ->where('codigo','like',"%{$t}%")
            ->orWhere('nombre','like',"%{$t}%")
            ->orWhere('simbolo','like',"%{$t}%")
            ->orWhere('tipo','like',"%{$t}%"));
    }


    public function productos(): HasMany
    {
        return $this->hasMany(\App\Models\Productos\Producto::class, 'unidad_medida_id');
    }
}
