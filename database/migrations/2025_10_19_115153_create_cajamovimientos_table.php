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
        Schema::create('cajamovimientos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('turno_id');   // turno al que pertenece
            $table->unsignedBigInteger('user_id');    // quien registró el movimiento

            // INGRESO | RETIRO | DEVOLUCION
            $table->enum('tipo', ['INGRESO','RETIRO','DEVOLUCION'])->index();

            $table->decimal('monto', 14, 2);          // valor positivo (los retiros los restas en reporte)
            $table->string('motivo')->nullable();     // comentario breve u observación

            $table->timestamps();

            $table->foreign('turno_id')
                  ->references('id')->on('turnos_caja')
                  ->cascadeOnDelete();
        });
       
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajamovimientos');
    }
};
