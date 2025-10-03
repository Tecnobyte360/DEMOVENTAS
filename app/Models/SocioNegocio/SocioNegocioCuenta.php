<?php

namespace App\Models\SocioNegocio;

use Illuminate\Database\Eloquent\Model;

class SocioNegocioCuenta extends Model
{
    protected $table = 'socio_negocio_cuentas';

    protected $fillable = [
        'socio_negocio_id',
        'cuenta_cxc_id','cuenta_anticipos_id','cuenta_descuentos_id',
        'cuenta_ret_fuente_id','cuenta_ret_ica_id','cuenta_iva_id',
    ];

    public function socio()
    {
        return $this->belongsTo(SocioNegocio::class,'socio_negocio_id');
    }

    // <- CLAVE: withDefault() para que tampoco sean null
    public function cuentaCxc()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class,'cuenta_cxc_id')->withDefault();
    }
    public function cuentaAnticipos()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class,'cuenta_anticipos_id')->withDefault();
    }
    public function cuentaDescuentos()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class,'cuenta_descuentos_id')->withDefault();
    }
    public function cuentaRetFuente()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class,'cuenta_ret_fuente_id')->withDefault();
    }
    public function cuentaRetIca()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class,'cuenta_ret_ica_id')->withDefault();
    }
    public function cuentaIva()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class,'cuenta_iva_id')->withDefault();
    }

    
}
