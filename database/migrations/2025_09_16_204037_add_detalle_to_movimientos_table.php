<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            // Agrega columna "detalle" si no existe
            if (!Schema::hasColumn('movimientos', 'detalle')) {
                $table->text('detalle')->nullable(); // TEXT (equivalente a LONGTEXT si se necesita más)
            }
            // Agrega columna "debe" si no existe
            if (!Schema::hasColumn('movimientos', 'debe')) {
                $table->decimal('debe', 18, 2)->default(0);
            }
            // Agrega columna "haber" si no existe
            if (!Schema::hasColumn('movimientos', 'haber')) {
                $table->decimal('haber', 18, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            // Borra columnas solo si existen
            if (Schema::hasColumn('movimientos', 'detalle')) {
                $table->dropColumn('detalle');
            }
            if (Schema::hasColumn('movimientos', 'debe')) {
                $table->dropColumn('debe');
            }
            if (Schema::hasColumn('movimientos', 'haber')) {
                $table->dropColumn('haber');
            }
        });
    }
};
