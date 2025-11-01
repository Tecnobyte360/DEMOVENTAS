<?php

namespace App\Models\Productos;

use App\Models\Bodega;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categorias\Subcategoria;
use App\Models\UnidadesMedida;

class Producto extends Model
{
    use HasFactory;

    public const MOV_SEGUN_ARTICULO     = 'ARTICULO';
    public const MOV_SEGUN_SUBCATEGORIA = 'SUBCATEGORIA';

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'costo',
        'precio',
        'activo',
        'subcategoria_id',
        'es_articulo_compra',
        'es_articulo_venta',
        'impuesto_id',
        'cuenta_ingreso_id',
        'mov_contable_segun',
        'unidad_medida_id',
        'imagen_path',
        'es_inventariable',
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'es_inventariable' => 'boolean',
        'costo'            => 'decimal:6',
        'precio'           => 'decimal:2',
    ];

    // Incluimos accesores útiles para la vista / API
    protected $appends = [
        'precio_con_iva',
        'imagen_url',
        'stock_total',
        'costos_por_bodega',
        'costo_promedio_global',
    ];

    /** ==================== Relaciones ==================== */

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function bodegas()
    {
        return $this->belongsToMany(Bodega::class, 'producto_bodega', 'producto_id', 'bodega_id')
            ->withPivot('stock', 'stock_minimo', 'stock_maximo', 'costo_promedio', 'ultimo_costo', 'metodo_costeo')
            ->withTimestamps();
    }

    public function precios()
    {
        return $this->hasMany(PrecioProducto::class);
    }

    public function impuesto()
    {
        return $this->belongsTo(\App\Models\Impuestos\Impuesto::class, 'impuesto_id');
    }

    public function cuentaIngreso()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_ingreso_id');
    }

    public function cuentas()
    {
        return $this->hasMany(\App\Models\Productos\ProductoCuenta::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadesMedida::class, 'unidad_medida_id');
    }

    /** ==================== Accessors / Mutators ==================== */

    /** Servicio = no inventariable */
    public function getEsServicioAttribute(): bool
    {
        return !$this->es_inventariable;
    }

    /** Precio con IVA (solo lectura). */
    public function getPrecioConIvaAttribute(): float
    {
        $base = (float)($this->precio ?? 0);
        $imp  = $this->impuesto;

        if (!$imp) return round($base, 2);
        if (!is_null($imp->porcentaje)) return round($base * (1 + ((float)$imp->porcentaje / 100)), 2);
        if (!is_null($imp->monto_fijo))  return round($base + (float)$imp->monto_fijo, 2);

        return round($base, 2);
    }

    /** URL para <img>. Si se guarda Base64, se devuelve tal cual. */
    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen_path ?: null;
    }

    /** Normaliza imagen_path (data-uri/base64). */
    public function setImagenPathAttribute($value): void
    {
        $v = is_string($value) ? trim($value) : null;
        if (!$v) {
            $this->attributes['imagen_path'] = null;
            return;
        }
        if (str_starts_with($v, 'data:image/')) {
            $this->attributes['imagen_path'] = $v;
            return;
        }
        $this->attributes['imagen_path'] = 'data:image/png;base64,' . $v;
    }

    /** Movimiento contable helpers */
    public function getMovSegunEsArticuloAttribute(): bool
    {
        return $this->mov_contable_segun === self::MOV_SEGUN_ARTICULO;
    }

    public function getMovSegunEsSubcategoriaAttribute(): bool
    {
        return $this->mov_contable_segun === self::MOV_SEGUN_SUBCATEGORIA;
    }

    /** Stock total (suma de pivotes) — útil en listados */
    public function getStockTotalAttribute(): ?float
    {
        if (!$this->relationLoaded('bodegas')) return null;
        return (float) $this->bodegas->sum(fn($b) => (float) ($b->pivot->stock ?? 0));
    }

    /** CPU de una bodega (si existe) o null. */
    public function costoPromedioEnBodega(?int $bodegaId): ?float
    {
        if (!$bodegaId) return null;
        $rel = $this->relationLoaded('bodegas')
            ? $this->bodegas->firstWhere('id', $bodegaId)
            : $this->bodegas()->where('bodega_id', $bodegaId)->first();

        if (!$rel) return null;
        $cpu = $rel->pivot?->costo_promedio;
        return is_null($cpu) ? null : round((float)$cpu, 6);
    }

    /** ==================== Costos / Helpers ==================== */

    /**
     * Accesor: costos por bodega como arreglo listo para la vista.
     * Estructura:
     * [
     *   bodega_id => [
     *     'bodega'         => string,
     *     'ultimo_costo'   => float|null,
     *     'costo_promedio' => float|null,
     *     'stock'          => float,
     *     'metodo_costeo'  => string|null,
     *   ],
     *   ...
     * ]
     */
    public function getCostosPorBodegaAttribute(): array
    {
        $this->loadMissing('bodegas');

        return $this->bodegas
            ->mapWithKeys(function ($b) {
                return [
                    $b->id => [
                        'bodega'         => $b->nombre,
                        'ultimo_costo'   => is_null($b->pivot->ultimo_costo) ? null : (float) $b->pivot->ultimo_costo,
                        'costo_promedio' => is_null($b->pivot->costo_promedio) ? null : (float) $b->pivot->costo_promedio,
                        'stock'          => (float) ($b->pivot->stock ?? 0),
                        'metodo_costeo'  => $b->pivot->metodo_costeo,
                    ],
                ];
            })
            ->all();
    }

    /**
     * Accesor: costo promedio global del producto.
     * - Si hay stock total > 0: promedio ponderado por stock.
     * - Si no hay stock: promedio simple de los costos promedio no nulos.
     */
    public function getCostoPromedioGlobalAttribute(): ?float
    {
        if (!$this->relationLoaded('bodegas')) return null;

        $totalStock = (float) $this->bodegas->sum(fn ($b) => (float) ($b->pivot->stock ?? 0));

        if ($totalStock > 0) {
            $sum = $this->bodegas->sum(function ($b) {
                $stock = (float) ($b->pivot->stock ?? 0);
                $cpu   = (float) ($b->pivot->costo_promedio ?? 0);
                return $stock * $cpu;
            });

            return round($sum / max($totalStock, 1), 6);
        }

        // Sin stock: promedio simple de CPUs no nulos
        $vals = $this->bodegas
            ->pluck('pivot.costo_promedio')
            ->filter(fn ($v) => !is_null($v))
            ->map(fn ($v) => (float) $v);

        return $vals->isEmpty() ? null : round($vals->avg(), 6);
    }
}
