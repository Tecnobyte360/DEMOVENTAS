<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('concepto_documento_cuenta', function (Blueprint $table) {
            $table->id();

            $table->foreignId('concepto_documento_id')
                ->constrained('conceptos_documentos')
                ->cascadeOnDelete();

            $table->foreignId('plan_cuenta_id')
                ->constrained('plan_cuentas')
                ->cascadeOnDelete();

            // Metadatos opcionales
            $table->string('rol', 40)->nullable();        // p.ej. inventario, gasto, contra, iva, ajuste, etc.
            $table->string('naturaleza', 10)->nullable(); // debito / credito (si aplica)
            $table->decimal('porcentaje', 9, 4)->nullable(); // Si alguna regla usa %
            $table->unsignedSmallInteger('prioridad')->default(0); // orden de aplicación

            $table->timestamps();

            $table->unique(['concepto_documento_id', 'plan_cuenta_id', 'rol'], 'concepto_cuenta_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concepto_documento_cuenta');
    }
};
