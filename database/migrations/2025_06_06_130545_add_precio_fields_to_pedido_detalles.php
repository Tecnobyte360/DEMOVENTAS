<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrecioFieldsToPedidoDetalles extends Migration
{
    public function up()
    {
        Schema::table('pedido_detalles', function (Blueprint $table) {

            // Agregar campo precio_unitario si no existe
            if (!Schema::hasColumn('pedido_detalles', 'precio_unitario')) {
                $table->decimal('precio_unitario', 12, 2)
                    ->after('cantidad')
                    ->default(0);
            }

            // Agregar campo precio_lista_id compatible con BIGINT y FK
            if (!Schema::hasColumn('pedido_detalles', 'precio_lista_id')) {
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
        Schema::table('pedido_detalles', function (Blueprint $table) {

            if (Schema::hasColumn('pedido_detalles', 'precio_lista_id')) {
                $table->dropForeign(['precio_lista_id']);
                $table->dropColumn('precio_lista_id');
            }

            if (Schema::hasColumn('pedido_detalles', 'precio_unitario')) {
                $table->dropColumn('precio_unitario');
            }
        });
    }
}
