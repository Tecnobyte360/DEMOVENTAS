<?php

namespace App\Models\MediosPago;

use Illuminate\Database\Eloquent\Model;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Productos\ProductoCuentaTipo;

class MedioPagoCuenta extends Model
{
    protected $table = 'medio_pago_cuentas';

    protected $fillable = [
        'medio_pago_id',
        'tipo_id',
        'plan_cuentas_id',
    ];

    protected $casts = [
        'medio_pago_id'   => 'integer',
        'tipo_id'         => 'integer',
        'plan_cuentas_id' => 'integer',
    ];

    public function medioPago()
    {
        return $this->belongsTo(MedioPagos::class, 'medio_pago_id');
    }

    public function tipo()
    {
        return $this->belongsTo(ProductoCuentaTipo::class, 'tipo_id');
    }

    public function cuentaPUC()
    {
        return $this->belongsTo(PlanCuentas::class, 'plan_cuentas_id');
    }
}
