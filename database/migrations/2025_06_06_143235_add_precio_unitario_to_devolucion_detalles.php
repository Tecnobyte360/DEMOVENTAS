<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('devolucion_detalles', function (Blueprint $table) {
            // Agregar precio_unitario solo si no existe
            if (!Schema::hasColumn('devolucion_detalles', 'precio_unitario')) {
                $table->decimal('precio_unitario', 12, 2)
                      ->default(0)
                      ->after('cantidad');
            }

            // Agregar precio_lista_id solo si no existe
            if (!Schema::hasColumn('devolucion_detalles', 'precio_lista_id')) {
                $table->unsignedBigInteger('precio_lista_id')
                      ->nullable()
                      ->after('precio_unitario');

                $table->foreign('precio_lista_id')
                      ->references('id')
                      ->on('precios_producto')
                      ->onDelete('no action');
            }
        });
    }

    public function down()
    {
        Schema::table('devolucion_detalles', function (Blueprint $table) {
            // Eliminar FK si existe
            if (Schema::hasColumn('devolucion_detalles', 'precio_lista_id')) {
                $table->dropForeign(['precio_lista_id']);
                $table->dropColumn('precio_lista_id');
            }

            // Eliminar precio_unitario si existe
            if (Schema::hasColumn('devolucion_detalles', 'precio_unitario')) {
                $table->dropColumn('precio_unitario');
            }
        });
    }
};
