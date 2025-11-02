<?php

namespace App\Models\CuentasContables;

use App\Models\Conceptos\ConceptoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class PlanCuentas extends Model
{
    protected $table = 'plan_cuentas';

    protected $fillable = [
        'codigo','nombre','nivel','padre_id',
        'naturaleza','cuenta_activa','titulo',
        'moneda','requiere_tercero','confidencial','nivel_confidencial',
        'clase_cuenta','cuenta_monetaria','cuenta_asociada','revalua_indice','bloquear_contab_manual',
        'relevante_flujo_caja','relevante_costos',
        'dimension1','dimension2','dimension3','dimension4',
        'saldo',
    ];

    protected $casts = [
        'nivel' => 'integer',
        'cuenta_activa' => 'boolean',
        'titulo' => 'boolean',
        'requiere_tercero' => 'boolean',
        'confidencial' => 'boolean',
        'nivel_confidencial' => 'integer',
        'cuenta_monetaria' => 'boolean',
        'cuenta_asociada' => 'boolean',
        'revalua_indice' => 'boolean',
        'bloquear_contab_manual' => 'boolean',
        'relevante_flujo_caja' => 'boolean',
        'relevante_costos' => 'boolean',
        'saldo' => 'decimal:2',
    ];

    /* ================= Relaciones ================= */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        // Orden natural por “código padded”
        return $this->hasMany(self::class, 'padre_id')->ordenCodigo();
    }

    /* ================= Scopes ================= */
    public function scopeRaices($q)
    {
        return $q->whereNull('padre_id')->ordenCodigo();
    }

    public function scopeActivas($q, bool $solo = true)
    {
        return $solo ? $q->where('cuenta_activa', true) : $q;
    }

    public function scopeNaturaleza($q, ?string $nat)
    {
        if (!$nat || strtoupper($nat) === 'TODAS') return $q;
        return $q->where('naturaleza', strtoupper($nat));
    }

    public function scopeBuscar($q, ?string $term)
    {
        if (!$term) return $q;
        $t = trim($term);
        return $q->where(fn($qq) => $qq
            ->where('codigo', 'like', "%{$t}%")
            ->orWhere('nombre', 'like', "%{$t}%"));
    }

    /**
     * ✅ Ordena por código con padding, compatible con MySQL/MariaDB, PostgreSQL, SQLite y SQL Server.
     */
    public function scopeOrdenCodigo($q, int $padLen = 20)
    {
        $driver = $q->getQuery()->getConnection()->getDriverName(); // 'mysql','pgsql','sqlite','sqlsrv'

        if ($driver === 'sqlsrv') {
            // Ej.: RIGHT(REPLICATE('0',20) + REPLACE(CAST(codigo AS varchar(255)),'.',''), 20)
            $expr = "RIGHT(REPLICATE('0', {$padLen}) + REPLACE(CAST(codigo AS varchar(255)), '.', ''), {$padLen})";
        } else {
            // MySQL, MariaDB, PostgreSQL, SQLite tienen LPAD
            $expr = "LPAD(REPLACE(codigo, '.', ''), {$padLen}, '0')";
        }

        return $q->orderByRaw("$expr ASC");
    }

    public function scopeImputables($q)
    {
        // cuenta imputable: no es título y está activa
        return $q->where(function ($w) {
            $w->where('titulo', 0)->orWhereNull('titulo');
        })->where('cuenta_activa', 1);
    }

    /* ================= Helpers UI ================= */
    public function getTieneHijosAttribute(): bool
    {
        if ($this->relationLoaded('hijos')) return $this->hijos->isNotEmpty();
        return $this->hijos()->exists();
    }

    public function getEsImputableAttribute(): bool
    {
        return !$this->titulo && $this->cuenta_activa;
    }

    public function getIndentPxAttribute(): int
    {
        $nivel = property_exists($this, 'nivel_visual') ? (int)$this->nivel_visual : (int)$this->nivel;
        return max(0, ($nivel - 1) * 18);
    }

    public function getRutaCodigoAttribute(): string
    {
        $nodo = $this; $partes = [];
        while ($nodo) { array_unshift($partes, $nodo->codigo); $nodo = $nodo->padre; }
        return implode(' > ', $partes);
    }

    public function setNaturalezaAttribute($v){ $this->attributes['naturaleza'] = $v ? strtoupper($v) : null; }
    public function setCodigoAttribute($v){ $this->attributes['codigo'] = trim((string)$v); }

    /**
     * Devuelve el árbol “aplanado” en orden jerárquico padre→hijos.
     */
    public static function flatTree(
        ?string $naturaleza = 'TODAS',
        ?int $nivelMax = null,
        bool $soloActivas = false,
        ?string $busqueda = null
    ): Collection {
        $items = static::query()
            ->when($soloActivas, fn($q) => $q->where('cuenta_activa', 1))
            ->naturaleza($naturaleza)
            ->buscar($busqueda)
            ->ordenCodigo()
            ->get();

        $porPadre = $items->groupBy('padre_id');

        $flat = collect();
        $walk = function ($padreId, $nivel) use (&$walk, $porPadre, $nivelMax, $flat) {
            foreach (($porPadre[$padreId] ?? collect()) as $nodo) {
                if (!is_null($nivelMax) && $nivel > $nivelMax) continue;
                $nodo->nivel_visual = $nivel;
                $flat->push($nodo);
                $walk($nodo->id, $nivel + 1);
            }
        };

        $walk(null, 1);
        return $flat;
    }

    /* ================= Borrado seguro ================= */
    protected static function booted()
    {
        static::deleting(function (self $cuenta) {
            $cuenta->hijos()->update(['padre_id' => null]);
        });
    }

    /**
     * Naturaleza por prefijo del código (compatible con SQL Server / MySQL / PG / SQLite).
     */
    public function scopeNaturalezaArbol($q, ?string $nat)
    {
        if (!$nat || strtoupper($nat) === 'TODAS') return $q;

        $map = [
            'ACTIVOS'     => '1',
            'PASIVOS'     => '2',
            'PATRIMONIO'  => '3',
            'INGRESOS'    => '4',
            'GASTOS'      => '5',
            'COSTOS'      => '6',
        ];

        $natU = strtoupper($nat);
        $pref = $map[$natU] ?? null;

        if ($pref) {
            $driver = $q->getQuery()->getConnection()->getDriverName();
            $cast   = ($driver === 'sqlsrv') ? "CAST(codigo AS varchar(255))" : "CAST(codigo AS CHAR)";
            // Quita puntos y toma el primer dígito del árbol
            return $q->whereRaw("LEFT(REPLACE($cast, '.', ''), 1) = ?", [$pref]);
        }

        // Fallback por columna naturaleza
        return $q->whereRaw("TRIM(UPPER(naturaleza)) = ?", [$natU]);
    }

    public function scopeClase($q, string $clase)
    {
        return $q->where('clase_cuenta', $clase);
    }

public function conceptos()
{
    return $this->belongsToMany(
        \App\Models\Conceptos\ConceptoDocumento::class,
        'concepto_documento_cuenta',
        'plan_cuenta_id',                     // FK hacia PlanCuentas en el pivote
        'concepto_documento_id'               // FK hacia ConceptoDocumento en el pivote
    )
    ->withPivot(['rol','naturaleza','porcentaje','prioridad'])
    ->withTimestamps();
}



}
