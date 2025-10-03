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
        Schema::create('salida_mercancia_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_mercancia_id')->constrained('salidas_mercancia')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('bodega_id')->constrained()->onDelete('cascade');
            $table->integer('cantidad');
            $table->string('concepto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salida_mercancia_detalles');
    }
};
