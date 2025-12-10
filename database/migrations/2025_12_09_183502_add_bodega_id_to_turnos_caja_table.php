<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            if (!Schema::hasColumn('turnos_caja', 'bodega_id')) {
                $table->unsignedBigInteger('bodega_id')->nullable()->after('user_id');
                $table->foreign('bodega_id')->references('id')->on('bodegas')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('turnos_caja', function (Blueprint $table) {
            $table->dropForeign(['bodega_id']);
            $table->dropColumn('bodega_id');
        });
    }
};
