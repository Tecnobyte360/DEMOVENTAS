<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            // ===== Relaciones (solo si NO existen) =====
            if (!Schema::hasColumn('movimientos', 'tercero_id')) {
                $table->unsignedBigInteger('tercero_id')->nullable()->after('cuenta_id');
            }
            if (!Schema::hasColumn('movimientos', 'factura_id')) {
                $table->unsignedBigInteger('factura_id')->nullable()->after('tercero_id');
            }
            if (!Schema::hasColumn('movimientos', 'factura_detalle_id')) {
                $table->unsignedBigInteger('factura_detalle_id')->nullable()->after('factura_id');
            }
            if (!Schema::hasColumn('movimientos', 'impuesto_id')) {
                $table->unsignedBigInteger('impuesto_id')->nullable()->after('factura_detalle_id');
            }
            if (!Schema::hasColumn('movimientos', 'centro_costo_id')) {
                $table->unsignedBigInteger('centro_costo_id')->nullable()->after('impuesto_id');
            }
            if (!Schema::hasColumn('movimientos', 'bodega_id')) {
                $table->unsignedBigInteger('bodega_id')->nullable()->after('centro_costo_id');
            }

            // ===== Campos fiscales (solo si NO existen) =====
            if (!Schema::hasColumn('movimientos', 'base_gravable')) {
                $table->decimal('base_gravable', 18, 2)->nullable()->after('credito');
            }
            if (!Schema::hasColumn('movimientos', 'tarifa_pct')) {
                $table->decimal('tarifa_pct', 9, 4)->nullable()->after('base_gravable');
            }
            if (!Schema::hasColumn('movimientos', 'valor_impuesto')) {
                $table->decimal('valor_impuesto', 18, 2)->nullable()->after('tarifa_pct');
            }

            // ===== Índices (crea nombres explícitos; se crearán si no existen) =====
            // Si vienes de un fallo, 'tercero_id' ya existe pero sin índice, así que esto ayuda.
            $table->index('tercero_id', 'movimientos_tercero_idx');
            $table->index('factura_id', 'movimientos_factura_idx');
            $table->index('impuesto_id', 'movimientos_impuesto_idx');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            // Quita índices por nombre
            $table->dropIndex('movimientos_tercero_idx');
            $table->dropIndex('movimientos_factura_idx');
            $table->dropIndex('movimientos_impuesto_idx');

            // Quita columnas (solo las que existan)
            $cols = [
                'valor_impuesto',
                'tarifa_pct',
                'base_gravable',
                'bodega_id',
                'centro_costo_id',
                'impuesto_id',
                'factura_detalle_id',
                'factura_id',
                'tercero_id',
            ];

            // Filtra a las que realmente existan para evitar errores en down()
            $toDrop = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('movimientos', $c)));
            if (!empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
