<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condicion_pagos', function (Blueprint $table) {
            $table->id(); // ⭐️ SOLO UNA definición de id

            $table->string('nombre', 120);
            $table->enum('tipo', ['contado','credito'])->default('contado');

            // Solo aplican cuando tipo = 'credito'
            $table->unsignedInteger('plazo_dias')->nullable();
            $table->decimal('interes_mora_pct', 8, 3)->nullable();
            $table->decimal('limite_credito', 18, 2)->nullable();
            $table->unsignedTinyInteger('tolerancia_mora_dias')->nullable();
            $table->unsignedTinyInteger('dia_corte')->nullable();

            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicion_pagos');
    }
};
