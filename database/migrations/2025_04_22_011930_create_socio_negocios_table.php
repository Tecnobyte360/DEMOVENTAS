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
        Schema::create('socio_negocios', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('nit')->unique();
            $table->string('telefono_fijo')->nullable();
            $table->string('telefono_movil')->nullable();
            $table->string('direccion');
            $table->string('correo')->nullable();
            $table->string('municipio_barrio')->nullable();
            $table->decimal('saldo_pendiente', 15, 2)->default(0);
            $table->string('Tipo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
