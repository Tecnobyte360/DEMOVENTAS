<?php

namespace App\Models\Productos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // <- para url pública
use App\Models\Categorias\Subcategoria;
use App\Models\bodegas;
use App\Models\UnidadesMedida;

class Producto extends Model
{
    use HasFactory;

    public const MOV_SEGUN_ARTICULO     = 'ARTICULO';
    public const MOV_SEGUN_SUBCATEGORIA = 'SUBCATEGORIA';

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
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Para que venga en ->toArray() / JSON sin llamarlo explícitamente
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
        return $this->belongsToMany(bodegas::class, 'producto_bodega', 'producto_id', 'bodega_id')
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

    /* ===================== Accessors ===================== */

    public function getPrecioConIvaAttribute(): float
    {
        $base = (float) ($this->precio ?? 0);
        $imp  = $this->impuesto;

        if (!$imp) return round($base, 2);

        if ($imp->porcentaje !== null) {
            return round($base * (1 + ((float) $imp->porcentaje / 100)), 2);
        }

        if ($imp->monto_fijo !== null) {
            return round($base + (float) $imp->monto_fijo, 2);
        }

        return round($base, 2);
    }

    /**
     * URL pública de la imagen almacenada en storage/app/public/...
     * Retorna null si no hay imagen.
     */
   public function getImagenUrlAttribute(): ?string
{
    if (!$this->imagen_path) {
        return null;
    }

    return \Illuminate\Support\Facades\Storage::url($this->imagen_path);
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
