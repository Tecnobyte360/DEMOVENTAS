<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('devolucion_detalles', function (Blueprint $table) {
            // Agrega campo precio_unitario
            $table->decimal('precio_unitario', 12, 2)
                  ->default(0)
                  ->after('cantidad');

            // Agrega campo precio_lista_id con el mismo tipo que ID de precio_productos
            $table->unsignedBigInteger('precio_lista_id')
                  ->nullable()
                  ->after('precio_unitario');

            // Clave foránea SIN cascada para evitar conflictos en SQL Server
            $table->foreign('precio_lista_id')
                  ->references('id')
                  ->on('precios_producto')
                  ->onDelete('no action');
        });
    }

    public function down()
    {
        Schema::table('devolucion_detalles', function (Blueprint $table) {
            // Eliminar la clave foránea y los campos agregados
            $table->dropForeign(['precio_lista_id']);
            $table->dropColumn(['precio_lista_id', 'precio_unitario']);
        });
    }
};
