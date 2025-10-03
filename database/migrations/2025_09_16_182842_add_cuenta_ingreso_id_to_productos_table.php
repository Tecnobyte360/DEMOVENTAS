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
         Schema::table('productos', function (Blueprint $table) {
            // Añadir la columna
            $table->unsignedBigInteger('cuenta_ingreso_id')->nullable()->after('impuesto_id');

            // Crear la relación con plan_cuentas
            $table->foreign('cuenta_ingreso_id')
                  ->references('id')
                  ->on('plan_cuentas')
                  ->nullOnDelete(); 
        });
    }

    
     public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['cuenta_ingreso_id']);
            $table->dropColumn('cuenta_ingreso_id');
        });
    }
};
