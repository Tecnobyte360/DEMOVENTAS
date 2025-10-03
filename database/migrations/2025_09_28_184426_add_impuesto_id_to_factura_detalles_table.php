<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('factura_detalles', function (Blueprint $table) {
        $table->unsignedBigInteger('impuesto_id')->nullable()->after('descuento_pct');

        $table->foreign('impuesto_id')
              ->references('id')
              ->on('impuestos')
              ->nullOnDelete();
    });
}

public function down()
{
    Schema::table('factura_detalles', function (Blueprint $table) {
        $table->dropForeign(['impuesto_id']);
        $table->dropColumn('impuesto_id');
    });
}

};
