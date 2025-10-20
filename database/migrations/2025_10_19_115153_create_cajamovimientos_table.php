<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cajamovimientos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('turno_id');   // turno al que pertenece
            $table->unsignedBigInteger('user_id');    // quien registró el movimiento

            // Tipos de movimiento
            $table->enum('tipo', ['INGRESO','RETIRO','DEVOLUCION'])->index();

            $table->decimal('monto', 14, 2);          // valor positivo
            $table->string('motivo')->nullable();     // comentario breve u observación

            $table->timestamps();

            // relaciones
            $table->foreign('turno_id')
                  ->references('id')->on('turnos_caja')
                  ->cascadeOnDelete();

            // opcional: relación a users
            // $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['turno_id','user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajamovimientos');
    }
};
