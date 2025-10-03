<?php

namespace App\Models\Categorias;

use App\Models\Categorias\Categoria;
use App\Models\Productos\Producto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;

    protected $fillable = ['categoria_id','nombre','descripcion','activo'];

    public function categoria()  { return $this->belongsTo(Categoria::class); }
    public function productos()  { return $this->hasMany(Producto::class); }
    public function cuentas()    { return $this->hasMany(SubcategoriaCuenta::class); }
}
