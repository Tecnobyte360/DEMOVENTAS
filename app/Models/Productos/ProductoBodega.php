<?php

namespace App\Models\Productos;

use App\Models\Bodega;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductoBodega extends Model
{
    use HasFactory;

    protected $table = 'producto_bodega';

    protected $fillable = [
        'producto_id',
        'bodega_id',
        'stock',
        'stock_minimo',
        'stock_maximo',
        // NUEVOS CAMPOS
        'costo_promedio',   // CPU por bodega
        'ultimo_costo',     // último costo comprado
        'metodo_costeo',    // PROMEDIO|PEPS (por ahora usamos PROMEDIO)
    ];

    protected $casts = [
        'stock'          => 'decimal:6',
        'stock_minimo'   => 'decimal:6',
        'stock_maximo'   => 'decimal:6',
        'costo_promedio' => 'decimal:6',
        'ultimo_costo'   => 'decimal:6',
    ];

    /* ===================== Relaciones ===================== */

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }

    /* ===================== Scopes ===================== */

    public function scopeFila($query, int $productoId, int $bodegaId)
    {
        return $query->where('producto_id', $productoId)->where('bodega_id', $bodegaId);
    }

    public function scopeDeProducto($query, int $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeDeBodega($query, int $bodegaId)
    {
        return $query->where('bodega_id', $bodegaId);
    }

    /* ===================== Mutators / Normalizadores ===================== */

    public function setMetodoCosteoAttribute($value): void
    {
        $v = strtoupper((string) $value);
        if (!in_array($v, ['PROMEDIO','PEPS'])) {
            $v = 'PROMEDIO';
        }
        $this->attributes['metodo_costeo'] = $v;
    }

    /* ===================== Helpers de costo ===================== */

    /**
     * Devuelve el costo unitario que se debe usar para SALIDAS.
     * Por ahora: costo_promedio; si no hay, último_costo; si no hay, costo global del producto.
     */
    public function costoUnitarioSalida(): float
    {
        if (is_numeric($this->costo_promedio) && (float)$this->costo_promedio > 0) {
            return round((float)$this->costo_promedio, 6);
        }
        if (is_numeric($this->ultimo_costo) && (float)$this->ultimo_costo > 0) {
            return round((float)$this->ultimo_costo, 6);
        }

        $prod = $this->relationLoaded('producto') ? $this->producto : $this->producto()->first();
        $fallback = $prod?->costo_promedio ?? $prod?->costo ?? 0;
        return round((float)$fallback, 6);
    }

    /**
     * Actualiza stock y costo promedio ante una ENTRADA.
     * - cantidad > 0
     * - costoUnitario puede venir de la factura de compra (con/sin impuestos según tu política).
     * Fórmula (promedio ponderado): CPU' = (CPU*Q + costo*cant) / (Q + cant)
     */
    public function registrarEntrada(float $cantidad, float $costoUnitario): self
    {
        if ($cantidad <= 0) {
            return $this;
        }

        return DB::transaction(function () use ($cantidad, $costoUnitario) {
            // Bloquea la fila para evitar race conditions
            $lock = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->first();

            $qActual = (float) ($lock->stock ?? 0);
            $cpu     = (float) ($lock->costo_promedio ?? 0);

            $nuevoQ   = $qActual + $cantidad;
            $nuevoCPU = $cpu;

            if ($nuevoQ > 0) {
                // promedio ponderado
                if ($cpu > 0) {
                    $nuevoCPU = (($cpu * $qActual) + ($costoUnitario * $cantidad)) / $nuevoQ;
                } else {
                    // si no teníamos CPU, el primero es el costo entrante
                    $nuevoCPU = $costoUnitario;
                }
            }

            $lock->update([
                'stock'          => round($nuevoQ, 6),
                'costo_promedio' => round($nuevoCPU, 6),
                'ultimo_costo'   => round($costoUnitario, 6),
                'metodo_costeo'  => $lock->metodo_costeo ?: 'PROMEDIO',
            ]);

            // refrescamos instancia actual
            return $this->fresh();
        });
    }

    /**
     * Registra una SALIDA (disminuye stock).
     * - cantidad > 0
     * - No cambia el costo_promedio (consumimos el vigente).
     * Retorna el costo_total consumido (cantidad * cpu).
     */
    public function registrarSalida(float $cantidad): float
    {
        if ($cantidad <= 0) {
            return 0.0;
        }

        return DB::transaction(function () use ($cantidad) {
            $lock = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->first();

            $qActual = (float) ($lock->stock ?? 0);
            if ($cantidad > $qActual) {
                throw new \RuntimeException('Stock insuficiente en bodega para realizar la salida.');
            }

            $cpu = $lock->costoUnitarioSalida(); // CPU vigente (promedio)
            $nuevoQ = $qActual - $cantidad;

            $lock->update([
                'stock' => round($nuevoQ, 6),
                // costo_promedio se mantiene igual en salidas
            ]);

            return round($cpu * $cantidad, 2);
        });
    }

    /**
     * Upsert rápido para inicializar/actualizar línea de producto-bodega,
     * útil desde formularios (stock mín/max y costos).
     */
    public static function upsertFila(
        int $productoId,
        int $bodegaId,
        ?float $stockMin = null,
        ?float $stockMax = null,
        ?float $costoProm = null,
        ?float $ultimoCosto = null,
        ?string $metodo = null
    ): self {
        $row = self::firstOrNew(['producto_id' => $productoId, 'bodega_id' => $bodegaId]);

        if (!is_null($stockMin))   $row->stock_minimo   = $stockMin;
        if (!is_null($stockMax))   $row->stock_maximo   = $stockMax;
        if (!is_null($costoProm))  $row->costo_promedio = $costoProm;
        if (!is_null($ultimoCosto))$row->ultimo_costo   = $ultimoCosto;
        if (!is_null($metodo))     $row->metodo_costeo  = $metodo;

        // Si es nueva fila, asegura defaults
        if (!$row->exists) {
            $row->stock          = $row->stock ?? 0;
            $row->costo_promedio = $row->costo_promedio ?? 0;
            $row->ultimo_costo   = $row->ultimo_costo ?? 0;
            $row->metodo_costeo  = $row->metodo_costeo ?: 'PROMEDIO';
        }

        $row->save();
        return $row;
    }
}
