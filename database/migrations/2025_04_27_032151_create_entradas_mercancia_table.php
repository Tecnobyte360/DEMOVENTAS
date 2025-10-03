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
        Schema::create('entradas_mercancia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_negocio_id')->constrained('socio_negocios')->onDelete('cascade');
            $table->date('fecha_contabilizacion');
            $table->string('lista_precio')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_mercancia');
    }
};
