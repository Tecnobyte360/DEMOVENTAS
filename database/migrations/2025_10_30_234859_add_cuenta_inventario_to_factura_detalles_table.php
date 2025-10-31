<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('factura_detalles', 'cuenta_inventario_id')) {
                // FK opcional a plan_cuentas (ajusta el nombre si tu tabla difiere)
                $table->unsignedBigInteger('cuenta_inventario_id')->nullable()->after('producto_id');

                // Si tienes tabla plan_cuentas, agrega la FK:
                if (Schema::hasTable('plan_cuentas')) {
                    $table->foreign('cuenta_inventario_id')
                          ->references('id')->on('plan_cuentas')
                          ->nullOnDelete();
                }

                // índice para búsquedas
                $table->index('cuenta_inventario_id', 'idx_fd_cuenta_inventario');
            }
        });

        // Relleno inicial desde la columna vieja si existiera
        if (Schema::hasColumn('factura_detalles', 'cuenta_inventario_id') &&
            Schema::hasColumn('factura_detalles', 'cuenta_ingreso_id')) {
            DB::table('factura_detalles')
                ->whereNull('cuenta_inventario_id')
                ->update([
                    'cuenta_inventario_id' => DB::raw('cuenta_ingreso_id')
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('factura_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('factura_detalles', 'cuenta_inventario_id')) {
                // Quita FK e índice si existen (ignora errores si no existieran)
                try { $table->dropForeign(['cuenta_inventario_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex('idx_fd_cuenta_inventario'); } catch (\Throwable $e) {}
                $table->dropColumn('cuenta_inventario_id');
            }
        });
    }
};
