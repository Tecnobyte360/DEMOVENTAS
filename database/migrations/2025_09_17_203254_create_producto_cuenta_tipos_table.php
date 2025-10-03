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
        Schema::create('producto_cuenta_tipos', function (Blueprint $table) {
            $table->id();

            // Código corto único, ejemplo: GASTO, INGRESO, COSTO, INVENTARIO
            $table->string('codigo', 40)->unique();

            // Nombre visible en formularios (ejemplo: "Cuenta de gastos")
            $table->string('nombre', 120);

            // Flags útiles para administración
            $table->boolean('obligatorio')->default(true);
            $table->boolean('activo')->default(true);

            // Para orden de despliegue en formularios
            $table->unsignedSmallInteger('orden')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_cuenta_tipos');
    }
};
