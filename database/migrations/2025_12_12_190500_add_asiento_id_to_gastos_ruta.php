<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up()
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->unsignedBigInteger('asiento_id')->nullable()->after('caja_movimiento_id');
            $table->foreign('asiento_id')->references('id')->on('asientos')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->dropForeign(['asiento_id']);
            $table->dropColumn('asiento_id');
        });
    }
};
