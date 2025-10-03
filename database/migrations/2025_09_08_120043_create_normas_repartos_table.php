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
        // Tabla principal
        Schema::create('normas_repartos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('descripcion')->nullable();
            $table->enum('dimension', [
                'CENTRO_DE_OPERACIONES',
                'UNIDAD_DE_NEGOCIO',
                'CENTRO_DE_COSTOS',
                'DEPARTAMENTOS'
            ]);
            $table->date('valido_desde');
            $table->date('valido_hasta')->nullable();
            $table->boolean('valido')->default(true);
            $table->boolean('imputacion_directa')->default(false);
            $table->boolean('asignar_importes_fijos')->default(false);
            $table->timestamps();
        });

        // Tabla detalles
        Schema::create('normas_reparto_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norma_reparto_id')
                  ->constrained('normas_repartos')
                  ->cascadeOnDelete();
            $table->string('codigo_centro');
            $table->string('nombre_centro')->nullable();
            $table->decimal('valor', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('normas_reparto_detalles');
        Schema::dropIfExists('normas_repartos');
    }
};
