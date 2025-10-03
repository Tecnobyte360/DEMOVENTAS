<?php

namespace App\Models\Serie;

use App\Models\Factura\factura;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Serie extends Model
{
    protected $table = 'series';

    protected $fillable = [
        'nombre','prefijo',
        'documento','es_default',
        'desde','hasta','proximo',
        'longitud',
        'resolucion','resolucion_fecha',
        'vigente_desde','vigente_hasta',
        'activa',
    ];

    protected $casts = [
        'desde'            => 'integer',
        'hasta'            => 'integer',
        'proximo'          => 'integer',
        'longitud'         => 'integer',
        'es_default'       => 'boolean',
        'resolucion_fecha' => 'date',
        'vigente_desde'    => 'date',
        'vigente_hasta'    => 'date',
        'activa'           => 'boolean',
    ];

    public function facturas(): HasMany { return $this->hasMany(factura::class, 'serie_id'); }

    public function scopeActiva(Builder $q): Builder
    {
        $today = now()->toDateString();
        return $q->where('activa', true)
                 ->where(fn($s)=>$s->whereNull('vigente_desde')->orWhere('vigente_desde','<=',$today))
                 ->where(fn($s)=>$s->whereNull('vigente_hasta')->orWhere('vigente_hasta','>=',$today));
    }

    public function scopeDeDocumento(Builder $q, string $doc): Builder { return $q->where('documento',$doc); }
    public function scopeDefault(Builder $q): Builder { return $q->where('es_default', true); }

    public static function defaultPara(string $doc): ?self
    { return static::activa()->deDocumento($doc)->default()->first(); }

    /** Toma el siguiente consecutivo en transacción (FOR UPDATE). */
    public function tomarConsecutivo(): int
    {
        return DB::transaction(function () {
            $row = self::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($row->proximo < (int)$row->desde) $row->proximo = (int)$row->desde;
            if ($row->proximo > (int)$row->hasta) {
                throw new RuntimeException('La serie ha agotado su rango de numeración.');
            }

            $n = (int)$row->proximo;
            $row->proximo = $n + 1;
            $row->save();

            return $n;
        }, 3);
    }
}
