<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medio_pago_cuentas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medio_pago_id')
                  ->constrained('medio_pagos')
                  ->cascadeOnDelete();

            // Reutiliza los tipos de cuenta (ProductoCuentaTipo).
            // Si no usas tipos, puedes dejarlo nullable u omitirlo.
            $table->foreignId('tipo_id')
                  ->nullable()
                  ->constrained('producto_cuenta_tipos')
                  ->nullOnDelete();

            $table->foreignId('plan_cuentas_id')
                  ->nullable()
                  ->constrained('plan_cuentas')
                  ->nullOnDelete();

            $table->timestamps();

            // Un tipo por medio de pago (evita duplicados)
            $table->unique(['medio_pago_id', 'tipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medio_pago_cuentas');
    }
};
