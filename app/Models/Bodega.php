<?php

namespace App\Models;

use App\Models\Productos\Producto;
use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    
    protected $table = 'bodegas';

    protected $fillable = ['nombre', 'ubicacion', 'activo'];

    public function productos()
    {
        return $this->belongsToMany(\App\Models\Productos\Producto::class, 'producto_bodega')
            ->withPivot('stock', 'stock_minimo', 'stock_maximo', 'costo_promedio', 'ultimo_costo', 'metodo_costeo')
            ->withTimestamps();
    }

    public function usuarios()
    {
        return $this->belongsToMany(\App\Models\User::class, 'bodega_user')->withTimestamps();
    }

    /**
     * Filtra bodegas accesibles para un usuario.
     * - Si el usuario tiene `bodegas.ver_todas` o es null, no aplica filtro.
     * - En caso contrario, restringe al pivot `bodega_user`.
     */
    public function scopeAccesibles($query, $user = null)
    {
        $user = $user ?: auth()->user();
        if (!$user || (method_exists($user, 'puedeVerTodasLasBodegas') && $user->puedeVerTodasLasBodegas())) {
            return $query;
        }

        // Back-compat: si el permiso 'bodegas.ver_todas' aún no se ha creado en la BD,
        // no aplicamos el filtro (todos ven todo). En cuanto se cree el permiso,
        // empieza a respetar la asignación por usuario.
        try {
            $permisoExiste = \Spatie\Permission\Models\Permission::where('name', 'bodegas.ver_todas')->exists();
        } catch (\Throwable $e) {
            $permisoExiste = false;
        }
        if (!$permisoExiste) {
            return $query;
        }

        $ids = method_exists($user, 'bodegasPermitidasIds')
            ? ($user->bodegasPermitidasIds() ?? [])
            : [];

        return $query->whereIn('bodegas.id', $ids ?: [0]);
    }
}
