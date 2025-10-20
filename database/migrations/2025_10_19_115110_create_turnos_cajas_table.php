<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Si la tabla NO existe, la crea desde cero
        if (!Schema::hasTable('turnos_caja')) {
            Schema::create('turnos_caja', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id');
                $t->timestamp('fecha_inicio');
                $t->timestamp('fecha_cierre')->nullable();
                $t->decimal('base_inicial', 14, 2)->default(0);

                $t->decimal('total_ventas', 14, 2)->default(0);
                $t->decimal('ventas_efectivo', 14, 2)->default(0);
                $t->decimal('ventas_debito', 14, 2)->default(0);
                $t->decimal('ventas_credito_tarjeta', 14, 2)->default(0);
                $t->decimal('ventas_transferencias', 14, 2)->default(0);
                $t->decimal('ventas_a_credito', 14, 2)->default(0);
                $t->decimal('devoluciones', 14, 2)->default(0);
                $t->decimal('ingresos_efectivo', 14, 2)->default(0);
                $t->decimal('retiros_efectivo', 14, 2)->default(0);

                $t->string('estado')->default('abierto');
                $t->json('resumen')->nullable();

                $t->timestamps();
                $t->index(['user_id','estado']);
            });
        } else {
            // Si la tabla YA existe, revisa y actualiza columnas que falten
            Schema::table('turnos_caja', function (Blueprint $t) {
                if (!Schema::hasColumn('turnos_caja', 'ventas_credito_tarjeta')) {
                    $t->decimal('ventas_credito_tarjeta', 14, 2)->default(0)->after('ventas_debito');
                }
                if (!Schema::hasColumn('turnos_caja', 'ventas_transferencias')) {
                    $t->decimal('ventas_transferencias', 14, 2)->default(0)->after('ventas_credito_tarjeta');
                }
                if (!Schema::hasColumn('turnos_caja', 'ventas_a_credito')) {
                    $t->decimal('ventas_a_credito', 14, 2)->default(0)->after('ventas_transferencias');
                }
                if (!Schema::hasColumn('turnos_caja', 'devoluciones')) {
                    $t->decimal('devoluciones', 14, 2)->default(0)->after('ventas_a_credito');
                }
                if (!Schema::hasColumn('turnos_caja', 'ingresos_efectivo')) {
                    $t->decimal('ingresos_efectivo', 14, 2)->default(0)->after('devoluciones');
                }
                if (!Schema::hasColumn('turnos_caja', 'retiros_efectivo')) {
                    $t->decimal('retiros_efectivo', 14, 2)->default(0)->after('ingresos_efectivo');
                }
                if (!Schema::hasColumn('turnos_caja', 'resumen')) {
                    $t->json('resumen')->nullable()->after('estado');
                }
                if (!Schema::hasColumn('turnos_caja', 'estado')) {
                    $t->string('estado')->default('abierto')->after('retiros_efectivo');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_caja');
    }
};
