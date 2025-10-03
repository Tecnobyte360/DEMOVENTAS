<?php

namespace App\Models\CuentasContables;

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
        // Orden natural por “código padded” (ver scope ordenCodigo)
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
     * Ordena por código con padding (SQL Server).
     * Si tus códigos tienen puntos (p.ej. 1.1.05) también queda bien.
     */
    public function scopeOrdenCodigo($q)
    {
        // 12 dígitos suele alcanzar para la mayoría de PUC; sube si necesitas más.
        return $q->orderByRaw("
            RIGHT(REPLICATE('0', 12) + REPLACE(CAST(codigo AS VARCHAR(50)), '.', ''), 12)
        ");
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
        // Evita N+1 si ya están cargados
        if ($this->relationLoaded('hijos')) {
            return $this->hijos->isNotEmpty();
        }
        return $this->hijos()->exists();
    }

    public function getEsImputableAttribute(): bool
    {
        return !$this->titulo && $this->cuenta_activa;
    }

    public function getIndentPxAttribute(): int
    {
        // usa nivel_visual si viene desde flatTree(), sino nivel de BD
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
     * Úsalo en tu Livewire para pintar la tabla.
     */
    public static function flatTree(
        ?string $naturaleza = 'TODAS',
        ?int $nivelMax = null,
        bool $soloActivas = false,
        ?string $busqueda = null
    ): Collection {
        // 1) Trae TODO lo necesario en un solo viaje
        $items = static::query()
            ->when($soloActivas, fn($q) => $q->where('cuenta_activa', 1))
            ->naturaleza($naturaleza)
            ->buscar($busqueda)
            ->ordenCodigo()             // orden base por código padded
            ->get();

        // 2) Agrupa por padre para recorrer en memoria
        $porPadre = $items->groupBy('padre_id');

        // 3) DFS para “aplanar” en orden jerárquico
        $flat = collect();
        $walk = function ($padreId, $nivel) use (&$walk, $porPadre, $nivelMax, $flat) {
            foreach (($porPadre[$padreId] ?? collect()) as $nodo) {
                if (!is_null($nivelMax) && $nivel > $nivelMax) continue;

                // anota nivel_visual para la UI (indentación)
                $nodo->nivel_visual = $nivel;
                $flat->push($nodo);

                // baja a los hijos
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
            // evita cascadas en SQL Server
            $cuenta->hijos()->update(['padre_id' => null]);
        });
    }
   

public function scopeNaturalezaArbol($q, ?string $nat)
{
    if (!$nat || strtoupper($nat) === 'TODAS') {
        return $q;
    }

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
        // Por árbol: todo lo que cuelga del dígito raíz (quita puntos si tu código tiene 1.1.05)
        return $q->whereRaw("
            LEFT(REPLACE(CAST(codigo AS VARCHAR(50)), '.', ''), 1) = ?
        ", [$pref]);
    }

    // Fallback por columna naturaleza, saneando espacios/case
    return $q->whereRaw("RTRIM(LTRIM(UPPER(naturaleza))) = ?", [$natU]);
}


    public function scopeClase($q, string $clase)
    {
        return $q->where('clase_cuenta', $clase);
    }

}
