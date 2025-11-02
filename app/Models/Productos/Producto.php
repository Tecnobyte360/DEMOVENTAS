<?php

namespace App\Models\Productos;

use App\Models\Bodega;
use App\Models\Categorias\Subcategoria;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Impuestos\Impuesto;
use App\Models\UnidadesMedida;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

        // 🔹 FKs recomendadas
        'cuenta_inventario_id',
        'cuenta_compra_id',

        // 🔹 Fallbacks texto (opcional, si aún los conservas)
        'cuenta_inventario',
        'cuenta_compra',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'es_inventariable'  => 'boolean',
        'es_articulo_compra'=> 'boolean',
        'es_articulo_venta' => 'boolean',
        'costo'             => 'decimal:6',
        'precio'            => 'decimal:2',
    ];

    // Se exponen en API/vistas
    protected $appends = [
        'precio_con_iva',
        'imagen_url',
        'stock_total',
        'costos_por_bodega',
        'costo_promedio_global',
        // 🔹 Strings de cuentas listos para mostrar
        'cuenta_inventario_str',
        'cuenta_compra_str',
    ];

    /* =========================================================
     |  Relaciones
     |=========================================================*/

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadesMedida::class, 'unidad_medida_id');
    }

    public function impuesto(): BelongsTo
    {
        return $this->belongsTo(Impuesto::class, 'impuesto_id');
    }

    /** Cuenta de ingresos (ventas) */
   public function cuentaIngreso()
{
    return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_ingreso_id');
}

    /** 🔹 Cuenta de inventario (FK recomendada) */
   public function cuentaInventario()
{
    return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_inventario_id');
}

public function cuentaCompra()
{
    return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_compra_id');
}



    public function getCuentaIngresoStrAttribute(): string
{
    if ($this->relationLoaded('cuentaIngreso') && $this->cuentaIngreso) {
        return trim(($this->cuentaIngreso->codigo ?? '').' — '.($this->cuentaIngreso->nombre ?? ''));
    }
    return (string) ($this->cuenta_ingreso ?? '');
}

    /** Si manejas cuentas por tipo en otra tabla */
    public function cuentas(): HasMany
    {
        return $this->hasMany(\App\Models\Productos\ProductoCuenta::class);
    }

    /** Precios por lista (si lo usas) */
    public function precios(): HasMany
    {
        return $this->hasMany(\App\Models\Productos\PrecioProducto::class);
    }

    /** Bodegas con pivote de stock y costos */
public function bodegas()
{
    return $this->belongsToMany(\App\Models\Bodega::class, 'producto_bodega', 'producto_id', 'bodega_id')
        ->withPivot(['stock','stock_minimo','stock_maximo','ultimo_costo','costo_promedio','metodo_costeo'])
        ->withTimestamps();
}

    /* =========================================================
     |  Accessors / Mutators
     |=========================================================*/

    /** Servicio = no inventariable */
    public function getEsServicioAttribute(): bool
    {
        return !($this->es_inventariable ?? false);
    }

    /** Precio con IVA calculado (solo lectura) */
    public function getPrecioConIvaAttribute(): float
    {
        $base = (float)($this->precio ?? 0);
        $imp  = $this->impuesto;

        if (!$imp) return round($base, 2);
        if (!is_null($imp->porcentaje)) return round($base * (1 + ((float)$imp->porcentaje / 100)), 2);
        if (!is_null($imp->monto_fijo))  return round($base + (float)$imp->monto_fijo, 2);

        return round($base, 2);
    }

    /** URL para <img>. Si guardas Base64 o data-uri, se devuelve tal cual */
    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen_path ?: null;
    }

    /** Normaliza imagen_path a data-uri si llega solo el base64 */
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

    /** Suma de stock (requiere relación bodegas cargada) */
    public function getStockTotalAttribute(): ?float
    {
        if (!$this->relationLoaded('bodegas')) return null;
        return (float) $this->bodegas->sum(fn($b) => (float) ($b->pivot->stock ?? 0));
    }

    /** CPU de una bodega en particular */
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

    /** Detalle de costos por bodega para la vista */
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

    /** Costo promedio global (ponderado por stock si existe) */
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

    /** 🔹 String “CODIGO — NOMBRE” para cuenta inventario con fallback */
    public function getCuentaInventarioStrAttribute(): string
    {
        if ($this->relationLoaded('cuentaInventario') && $this->cuentaInventario) {
            return trim(($this->cuentaInventario->codigo ?? '').' — '.($this->cuentaInventario->nombre ?? ''));
        }
        // Fallback a texto plano si aún no migras a FK
        return (string) ($this->cuenta_inventario ?? '');
    }

    /** 🔹 String “CODIGO — NOMBRE” para cuenta compra con fallback */
    public function getCuentaCompraStrAttribute(): string
    {
        if ($this->relationLoaded('cuentaCompra') && $this->cuentaCompra) {
            return trim(($this->cuentaCompra->codigo ?? '').' — '.($this->cuentaCompra->nombre ?? ''));
        }
        // Fallback a texto plano si aún no migras a FK
        return (string) ($this->cuenta_compra ?? '');
    }

    /* =========================================================
     |  Scopes y helpers de consulta
     |=========================================================*/

    /** Scope: solo activos */
    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    /** Scope: búsqueda simple por nombre/código */
    public function scopeBusca($q, ?string $term)
    {
        $t = trim((string)$term);
        if ($t === '') return $q;

        return $q->where(function ($qq) use ($t) {
            $qq->where('nombre', 'like', "%{$t}%")
               ->orWhere('descripcion', 'like', "%{$t}%");
        });
    }

    /** Helper: eager básico recomendado para listas/formularios */
    public function scopeWithCuentasBasicas($q)
    {
        return $q->with([
            'cuentaInventario:id,codigo,nombre',
            'cuentaCompra:id,codigo,nombre',
            'cuentaIngreso:id,codigo,nombre',
        ]);
    }

    
}
