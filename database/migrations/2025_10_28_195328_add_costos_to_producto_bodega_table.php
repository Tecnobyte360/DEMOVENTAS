<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('producto_bodega', function (Blueprint $table) {
            if (!Schema::hasColumn('producto_bodega', 'costo_promedio')) {
                $table->decimal('costo_promedio', 18, 6)->default(0)->after('stock_maximo');
            }
            if (!Schema::hasColumn('producto_bodega', 'ultimo_costo')) {
                $table->decimal('ultimo_costo', 18, 6)->nullable()->after('costo_promedio');
            }
            if (!Schema::hasColumn('producto_bodega', 'metodo_costeo')) {
                $table->string('metodo_costeo', 20)->default('PROMEDIO')->after('ultimo_costo');
            }
        });
    }

    public function down(): void {
        Schema::table('producto_bodega', function (Blueprint $table) {
            if (Schema::hasColumn('producto_bodega', 'metodo_costeo')) $table->dropColumn('metodo_costeo');
            if (Schema::hasColumn('producto_bodega', 'ultimo_costo'))  $table->dropColumn('ultimo_costo');
            if (Schema::hasColumn('producto_bodega', 'costo_promedio')) $table->dropColumn('costo_promedio');
        });
    }
};
