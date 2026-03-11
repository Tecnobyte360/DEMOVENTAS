<?php

namespace App\Models\Factura;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'empresa_id',
        'serie_id',
        'socio_negocio_id',
        'cliente_id',
        'fecha',
        'vencimiento',
        'tipo_pago',
        'plazo_dias',
        'terminos_pago',
        'notas',
        'moneda',
        'estado',
        'subtotal',
        'impuestos',
        'total',
        'pagado',
        'saldo',
        'cuenta_cobro_id',
        'condicion_pago_id',
        'creado_por_id',
        'actualizado_por_id',
        'anulado_por_id',
        'emitido_por_id',
        'anulado_en',
        'emitido_en',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'vencimiento' => 'date',
        'subtotal'    => 'float',
        'impuestos'   => 'float',
        'total'       => 'float',
        'pagado'      => 'float',
        'saldo'       => 'float',
        'anulado_en'  => 'datetime',
        'emitido_en'  => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(\App\Models\ConfiguracionEmpresas\Empresa::class, 'empresa_id');
    }

    public function serie()
    {
        return $this->belongsTo(\App\Models\Serie\Serie::class, 'serie_id');
    }

    public function cliente()
    {
        return $this->belongsTo(\App\Models\SocioNegocio\SocioNegocio::class, 'socio_negocio_id');
    }

    public function socioNegocio()
    {
        return $this->belongsTo(\App\Models\SocioNegocio\SocioNegocio::class, 'socio_negocio_id');
    }

    public function detalles()
    {
        return $this->hasMany(\App\Models\Factura\FacturaDetalle::class, 'factura_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }

    public function emitidoPor()
    {
        return $this->belongsTo(User::class, 'emitido_por_id');
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por_id');
    }
    public function recalcularTotales(): self
{
    $detalles = $this->relationLoaded('detalles')
        ? $this->detalles
        : $this->detalles()->get();

    $subtotal = 0.0;
    $impuestos = 0.0;

    foreach ($detalles as $d) {
        $cantidad = (float) ($d->cantidad ?? 0);
        $precio = (float) ($d->precio_unitario ?? 0);
        $descuentoPct = (float) ($d->descuento_pct ?? 0);
        $impuestoPct = (float) ($d->impuesto_pct ?? 0);

        if ($cantidad <= 0) {
            continue;
        }

        $base = $cantidad * $precio * (1 - ($descuentoPct / 100));
        $iva = $base * ($impuestoPct / 100);

        $subtotal += $base;
        $impuestos += $iva;
    }

    $this->subtotal = round($subtotal, 2);
    $this->impuestos = round($impuestos, 2);
    $this->total = round($subtotal + $impuestos, 2);

    return $this;
}
}