<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'socio_negocio_id',
        'monto',
        'fecha',
        'metodo_pago',
        'observaciones',
        'user_id'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'float',
    ];

    public function pedido()
    {
        return $this->belongsTo(\App\Models\Pedidos\Pedido::class);
    }

    public function socioNegocio()
    {
        return $this->belongsTo(\App\Models\SocioNegocio\SocioNegocio::class);
    }
    public function usuario()
{
    return $this->belongsTo(User::class, 'user_id');
}

}
