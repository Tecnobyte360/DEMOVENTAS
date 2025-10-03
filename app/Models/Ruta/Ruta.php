<?php

namespace App\Models\Ruta;

use App\Models\InventarioRuta\GastoRuta;
use App\Models\InventarioRuta\InventarioRuta;
use App\Models\Pedidos\Pedido;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Vehiculo\Vehiculo;

class Ruta extends Model
{
    use HasFactory;

    protected $table = 'rutas';

    protected $fillable = [
        'vehiculo_id',
        'ruta',
        'fecha_salida',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function conductores()
    {
        return $this->belongsToMany(User::class, 'conductor_ruta', 'ruta_id', 'user_id')
            ->withPivot('aprobada')
            ->withTimestamps();
    }

    public function inventarios()
    {
        return $this->hasMany(InventarioRuta::class);
    }
    public function gastos()
    {
        return $this->hasMany(GastoRuta::class);
    }
    public function conductor()
    {
        return $this->belongsTo(User::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
    public function getNombreAttribute()
{
    return $this->attributes['nombre']
        ?? ($this->attributes['nombre_ruta'] ?? null)
        ?? ($this->attributes['descripcion'] ?? null);
}

}
