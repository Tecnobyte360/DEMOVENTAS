<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medio_pagos', function (Blueprint $t) {
            if (!Schema::hasColumn('medio_pagos', 'requiere_turno')) {
                $t->boolean('requiere_turno')->default(false)
                    ->comment('Si requiere un turno de caja abierto para registrar pagos.');
            }

            if (!Schema::hasColumn('medio_pagos', 'crear_movimiento')) {
                $t->boolean('crear_movimiento')->default(false)
                    ->comment('Si debe generar un registro en la tabla cajamovimientos.');
            }

            if (!Schema::hasColumn('medio_pagos', 'tipo_movimiento')) {
                $t->string('tipo_movimiento', 20)->default('INGRESO')
                    ->comment('Tipo de movimiento: INGRESO o EGRESO.');
            }

            if (!Schema::hasColumn('medio_pagos', 'contar_en_total')) {
                $t->boolean('contar_en_total')->default(true)
                    ->comment('Si debe sumar en turnos_caja.total_ventas.');
            }

            if (!Schema::hasColumn('medio_pagos', 'clave_turno')) {
                $t->string('clave_turno', 50)->nullable()
                    ->comment('Nombre de la columna en turnos_caja que debe incrementar (opcional).');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medio_pagos', function (Blueprint $t) {
            foreach (['requiere_turno', 'crear_movimiento', 'tipo_movimiento', 'contar_en_total', 'clave_turno'] as $col) {
                if (Schema::hasColumn('medio_pagos', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
