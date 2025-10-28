<?php

namespace App\Models\Serie;

use App\Models\Factura\Factura;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Serie extends Model
{
    protected $table = 'series';

    protected $fillable = [
        'nombre','prefijo',
        'tipo_documento_id','es_default',
        'desde','hasta','proximo',
        'longitud',
        'resolucion','resolucion_fecha',
        'vigente_desde','vigente_hasta',
        'activa',
    ];

    protected $casts = [
        'tipo_documento_id' => 'integer',
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

    public function tipo(): BelongsTo
    {
        // FK: tipo_documento_id, PK: id
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id', 'id');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'serie_id');
    }

    public function scopeActiva(Builder $q): Builder
    {
        $today = now()->toDateString();

        return $q->where('activa', true)
            ->where(fn($s) => $s->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', $today))
            ->where(fn($s) => $s->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $today));
    }

    public function scopeDeTipo(Builder $q, int $tipoId): Builder
    {
        return $q->where('tipo_documento_id', $tipoId);
    }

    public function scopeDefault(Builder $q): Builder
    {
        return $q->where('es_default', true);
    }

    /** Default por código de tipo (case-insensitive, ej: 'facturacompra' / 'FACTURACOMPRA') */
    public static function defaultParaCodigo(string $codigo): ?self
    {
        $tipo = TipoDocumento::whereRaw('LOWER(codigo) = ?', [strtolower($codigo)])->first();
        if (!$tipo) return null;

        return static::activa()
            ->deTipo((int) $tipo->id)
            ->default()
            ->first();
    }

    /** Default por id de tipo */
    public static function defaultParaTipo(int $tipoId): ?self
    {
        return static::activa()->deTipo($tipoId)->default()->first();
    }

    /** Toma el siguiente consecutivo en transacción (FOR UPDATE). */
    public function tomarConsecutivo(): int
    {
        return DB::transaction(function () {
            $row = self::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($row->proximo < (int) $row->desde) {
                $row->proximo = (int) $row->desde;
            }
            if ($row->proximo > (int) $row->hasta) {
                throw new RuntimeException('La serie ha agotado su rango de numeración.');
            }

            $n = (int) $row->proximo;
            $row->proximo = $n + 1;
            $row->save();

            return $n;
        }, 3);
    }
  
}
