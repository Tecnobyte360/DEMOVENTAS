<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socio_direcciones', function (Blueprint $table) {
            $table->id();

            // FK al socio
            $table->unsignedBigInteger('socio_negocio_id');

            // Datos de la dirección
            $table->string('tipo', 30)->default('entrega'); // entrega|facturacion|medios_magneticos|otro
            $table->string('direccion', 255);
            $table->string('barrio', 120)->nullable();

            // Municipio como FK a catálogo (si lo manejas)
            // Si no tienes la tabla 'municipios', cambia a string('municipio',120)->nullable()
            $table->unsignedBigInteger('municipio_id')->nullable();

            $table->string('contacto', 150)->nullable();
            $table->string('telefono', 60)->nullable();

            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Claves foráneas
            $table->foreign('socio_negocio_id')
                  ->references('id')->on('socio_negocios')
                  ->cascadeOnDelete(); // elimina direcciones si se elimina el socio

            // Si tienes tabla municipios:
            $table->foreign('municipio_id')
                  ->references('id')->on('municipios');

            // Índices útiles
            $table->index(['socio_negocio_id', 'tipo']);
            $table->index(['socio_negocio_id', 'es_principal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socio_direcciones');
    }
};
