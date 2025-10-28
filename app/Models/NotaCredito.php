<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaCredito extends Model
{
    protected $table = 'nota_creditos';
    protected $guarded = [];

    protected $casts = [
        'fecha'               => 'date',
        'vencimiento'         => 'date',
        'subtotal'            => 'decimal:2',
        'impuestos'           => 'decimal:2',
        'total'               => 'decimal:2',
        'reponer_inventario'  => 'boolean',  
    ];

    // Si quieres un default
    protected $attributes = [
        'reponer_inventario' => 1, // 1 = true
    ];

    public function detalles()      { return $this->hasMany(NotaCreditoDetalle::class); }
    public function factura()       { return $this->belongsTo(\App\Models\Factura\Factura::class); }
    public function serie()         { return $this->belongsTo(\App\Models\Serie\Serie::class); }
    public function cliente()       { return $this->belongsTo(\App\Models\SocioNegocio\SocioNegocio::class, 'socio_negocio_id'); }
    public function cuentaCobro()   { return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_cobro_id'); }
    public function condicionPago() { return $this->belongsTo(\App\Models\CondicionPago\CondicionPago::class, 'condicion_pago_id'); }

    /* Helpers de totales (igual que Factura) */
    public function recalcularTotales(): static
    {
        $sub = 0; $imp = 0;
        foreach ($this->detalles as $d) {
            $base = max(0, (float)$d->cantidad) * max(0, (float)$d->precio_unitario);
            $base = $base * (1 - max(0, min(100, (float)$d->descuento_pct)) / 100);
            $sub += $base;
            $imp += $base * max(0, (float)$d->impuesto_pct) / 100;
        }
        $this->subtotal  = round($sub, 2);
        $this->impuestos = round($imp, 2);
        $this->total     = round($sub + $imp, 2);
        return $this;
    }
    public function notasCredito(): HasMany
{
    // tabla: nota_creditos, FK: factura_id
    return $this->hasMany(\App\Models\NotaCredito::class, 'factura_id');
}


}
