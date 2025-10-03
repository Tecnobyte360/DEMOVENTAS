<?php

namespace App\Models\Categorias;

use App\Models\Categorias\Subcategoria;  // Corregido el namespace
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class);  // Corregido la referencia a Subcategoria
    }
}
