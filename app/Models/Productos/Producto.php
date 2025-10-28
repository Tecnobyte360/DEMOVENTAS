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
         'es_inventariable'
    ];

    protected $casts = [
        'activo' => 'boolean',
         'es_inventariable' => 'boolean',
    ];
     public function getEsServicioAttribute(): bool
    {
        return !$this->es_inventariable;
    }

  
    protected $appends = [
        'precio_con_iva',
        'imagen_url',
    ];

    /* ===================== Relaciones ===================== */

     public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function bodegas()
    {
        return $this->belongsToMany(Bodega::class, 'producto_bodega', 'producto_id', 'bodega_id')
            ->withPivot('stock', 'stock_minimo', 'stock_maximo')
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
    /* ===================== Accessors / Mutators ===================== */

    /**
     * Precio con IVA (solo lectura).
     */
    public function getPrecioConIvaAttribute(): float
    {
        $base = (float)($this->precio ?? 0);
        $imp  = $this->impuesto;

        if (!$imp) return round($base, 2);
        if (!is_null($imp->porcentaje)) return round($base * (1 + ((float)$imp->porcentaje / 100)), 2);
        if (!is_null($imp->monto_fijo))  return round($base + (float)$imp->monto_fijo, 2);

        return round($base, 2);
    }

    /**
     * URL para <img src="...">. Como guardamos Base64, devolvemos tal cual.
     */
    public function getImagenUrlAttribute(): ?string
    {
        return $this->imagen_path ?: null;
    }

    /**
     * Mutator: Normaliza lo que se asigne a imagen_path.
     * - Si llega vacío => null
     * - Si llega data-uri (data:image/...;base64,xxx) => guarda tal cual
     * - Si llega solo base64 => le antepone data-uri con image/png
     */
    public function setImagenPathAttribute($value): void
    {
        $v = is_string($value) ? trim($value) : null;
        if (!$v) {
            $this->attributes['imagen_path'] = null;
            return;
        }

        // ¿Ya es data-uri completa?
        if (str_starts_with($v, 'data:image/')) {
            $this->attributes['imagen_path'] = $v;
            return;
        }

        // ¿Parece base64 “pelado”? (sin prefijo)
        // Nota: no validamos exhaustivamente; asumimos que ya se validó en el componente.
        $this->attributes['imagen_path'] = 'data:image/png;base64,'.$v;
    }

    /* ===================== Helpers ===================== */

    public function getMovSegunEsArticuloAttribute(): bool
    {
        return $this->mov_contable_segun === self::MOV_SEGUN_ARTICULO;
    }

    public function getMovSegunEsSubcategoriaAttribute(): bool
    {
        return $this->mov_contable_segun === self::MOV_SEGUN_SUBCATEGORIA;
    }
}
