<?php

namespace App\Models\Categorias;

use App\Models\CuentasContables\PlanCuentas;
use App\Models\Productos\ProductoCuentaTipo;
use Illuminate\Database\Eloquent\Model;

class SubcategoriaCuenta extends Model
{
    protected $table = 'subcategoria_cuentas';

    protected $fillable = [
        'subcategoria_id',
        'tipo_id',
        'plan_cuentas_id',
    ];

    public function subcategoria() { return $this->belongsTo(Subcategoria::class); }
    public function tipo()        { return $this->belongsTo(ProductoCuentaTipo::class, 'tipo_id'); }
    public function cuentaPUC()   { return $this->belongsTo(PlanCuentas::class, 'plan_cuentas_id'); }
}
