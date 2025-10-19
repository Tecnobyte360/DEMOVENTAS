<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_credito_detalles', function (Blueprint $table) {
            // Columna para vincular la línea de la nota crédito con la línea original de factura
            if (!Schema::hasColumn('nota_credito_detalles', 'factura_detalle_id')) {
                $table->unsignedBigInteger('factura_detalle_id')->nullable()->after('id');

                // Índice para mejorar búsquedas por factura_detalle_id
                $table->index('factura_detalle_id', 'idx_factura_detalle_id');

                // Clave foránea (solo si la tabla factura_detalles existe)
                $table->foreign('factura_detalle_id', 'fk_nota_factura_detalle')
                      ->references('id')
                      ->on('factura_detalles')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('nota_credito_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('nota_credito_detalles', 'factura_detalle_id')) {
                $table->dropForeign('fk_nota_factura_detalle');
                $table->dropIndex('idx_factura_detalle_id');
                $table->dropColumn('factura_detalle_id');
            }
        });
    }
};
