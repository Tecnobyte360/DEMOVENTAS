<?php

namespace App\Models\Productos;

use Illuminate\Database\Eloquent\Model;

class ProductoCuenta extends Model {
    protected $table = 'producto_cuentas';
    protected $fillable = ['producto_id','plan_cuentas_id','tipo_id'];

    public function producto()  { return $this->belongsTo(\App\Models\Productos\Producto::class); }
    public function cuentaPUC() { return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class,'plan_cuentas_id'); }
    public function tipo()      { return $this->belongsTo(ProductoCuentaTipo::class,'tipo_id'); }
}