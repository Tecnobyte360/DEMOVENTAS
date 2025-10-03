<?php

namespace App\Models\Pedidos;

use App\Models\Pago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'ruta_id',
        'socio_negocio_id',
        'user_id',
        'fecha',
        'tipo_pago',
         'valor_credito',
          'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones
    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class);
    }

    public function socioNegocio()
    {
        return $this->belongsTo(\App\Models\SocioNegocio\SocioNegocio::class, 'socio_negocio_id');
    }

    public function ruta()
    {
        return $this->belongsTo(\App\Models\Ruta\Ruta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

    // Métodos adicionales

    /**
     * Total del pedido (suma de cantidades x precio).
     */
   public function montoTotal()
{
    return $this->detalles->sum(function ($detalle) {
        $precio = $detalle->precio_aplicado ?? $detalle->precio_unitario;
        return $detalle->cantidad * floatval($precio);
    });
}

    /**
     * Total pagado (sumatoria de pagos asociados).
     */
    public function montoPagado()
    {
        return $this->pagos->sum('monto');
    }

    /**
     * Total pendiente de pago.
     */
   public function montoPendiente()
{
    return max($this->montoTotal() - $this->montoPagado(), 0);
}
    /**
     * ¿Está saldado el pedido?
     */
    public function estaPagado()
    {
        return $this->montoPendiente() <= 0;
    }
    public function conductor()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
