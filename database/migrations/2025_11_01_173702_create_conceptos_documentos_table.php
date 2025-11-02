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
       
        Schema::create('conceptos_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 180);
            // entrada / salida / ajuste (deja string para flexibilidad)
            $table->string('tipo', 20)->default('entrada')->index(); 
            $table->boolean('activo')->default(true)->index();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos_documentos');
    }
};
