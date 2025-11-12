<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaCredito extends Model
{
    protected $table = 'nota_creditos';
    protected $guarded = [];

    protected $casts = [
        'fecha'       => 'date',
        'vencimiento' => 'date',
        'subtotal'    => 'float',
        'impuestos'   => 'float',
        'total'       => 'float',
        'pagado'      => 'float',
    ];

    protected $attributes = [
        'reponer_inventario' => 1, // 1 = true
    ];

    /* ==================== RELACIONES ==================== */

    public function detalles(): HasMany
    {
        return $this->hasMany(NotaCreditoDetalle::class);
    }

    public function factura()
    {
        return $this->belongsTo(\App\Models\Factura\Factura::class);
    }

    public function serie()
    {
        return $this->belongsTo(\App\Models\Serie\Serie::class);
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\SocioNegocio\SocioNegocio::class, 'socio_negocio_id');
    }

    public function cuentaCobro()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_cobro_id');
    }

    public function condicionPago()
    {
        return $this->belongsTo(\App\Models\CondicionPago\CondicionPago::class, 'condicion_pago_id');
    }

    // Notas de crédito asociadas a una factura
    public function notasCredito(): HasMany
    {
        return $this->hasMany(self::class, 'factura_id');
    }

    /* ==================== SANITIZADORES ==================== */

    public function setSubtotalAttribute($v){ $this->attributes['subtotal']  = $this->toFloat($v); }
    public function setImpuestosAttribute($v){ $this->attributes['impuestos'] = $this->toFloat($v); }
    public function setTotalAttribute($v){ $this->attributes['total']      = $this->toFloat($v); }
    public function setPagadoAttribute($v){ $this->attributes['pagado']     = $this->toFloat($v); }

    private function toFloat($v): float
    {
        if ($v === null) return 0.0;
        if (is_int($v) || is_float($v)) return (float)$v;

        $s = (string)$v;
        $s = str_replace(["\u{00A0}", ' '], '', $s);        // quita espacios/nbsp
        $s = preg_replace('/[^\d,.\-]/', '', $s) ?? '';     // deja solo dígitos, , . y -
        $hasComma = strpos($s, ',') !== false;
        $hasDot   = strpos($s, '.') !== false;

        if ($hasComma && !$hasDot) $s = str_replace(',', '.', $s); // coma decimal
        else                       $s = str_replace(',', '', $s);  // coma de miles

        return (float)$s;
    }

    /* ==================== TOTALES ==================== */

    public function recalcularTotales(): static
    {
        $sub = 0.0;
        $imp = 0.0;

        $this->loadMissing('detalles');

        foreach ($this->detalles as $d) {
            $cant = (float) $d->cantidad;
            $pu   = (float) $d->precio_unitario;
            $desc = min(100, max(0, (float) $d->descuento_pct));
            $iva  = min(100, max(0, (float) $d->impuesto_pct));

            $base = $cant * $pu * (1 - $desc / 100);
            $sub += $base;
            $imp += $base * $iva / 100;
        }

        $this->subtotal  = round($sub, 2);
        $this->impuestos = round($imp, 2);
        $this->total     = round($this->subtotal + $this->impuestos, 2);

        return $this;
    }
}
