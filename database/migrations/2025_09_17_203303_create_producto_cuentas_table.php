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
        Schema::create('producto_cuentas', function (Blueprint $table) {
            $table->id();

            // Claves foráneas
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('plan_cuentas_id');
            $table->unsignedBigInteger('tipo_id'); 

            $table->timestamps();

            // Relaciones
            $table->foreign('producto_id')
                  ->references('id')->on('productos')
                  ->onDelete('cascade');

            $table->foreign('plan_cuentas_id')
                  ->references('id')->on('plan_cuentas');

            $table->foreign('tipo_id')
                  ->references('id')->on('producto_cuenta_tipos');

            $table->unique(['producto_id', 'tipo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_cuentas');
    }
};
