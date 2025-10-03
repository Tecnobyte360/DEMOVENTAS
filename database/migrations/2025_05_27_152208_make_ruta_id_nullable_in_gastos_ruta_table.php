<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            // Primero elimina la restricción de clave foránea si es necesario
            $table->dropForeign(['ruta_id']);

            // Luego modifica la columna para que sea nullable
            $table->foreignId('ruta_id')->nullable()->change();

            // Finalmente vuelve a agregar la restricción de clave foránea
            $table->foreign('ruta_id')->references('id')->on('rutas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            $table->dropForeign(['ruta_id']);
            $table->foreignId('ruta_id')->nullable(false)->change();
            $table->foreign('ruta_id')->references('id')->on('rutas')->onDelete('cascade');
        });
    }
};
