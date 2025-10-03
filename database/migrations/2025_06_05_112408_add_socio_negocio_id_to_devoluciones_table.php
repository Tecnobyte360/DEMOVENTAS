<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('devoluciones', function (Blueprint $table) {
        $table->unsignedBigInteger('socio_negocio_id')->nullable()->after('ruta_id');
        $table->foreign('socio_negocio_id')->references('id')->on('socio_negocios');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devoluciones', function (Blueprint $table) {
            //
        });
    }
};
