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
    Schema::create('nota_creditos', function (Blueprint $table) {
        $table->id();

        // Relación con la factura que cancela (opcional para NC independientes)
        $table->unsignedBigInteger('factura_id')->nullable()->index();

        // Serie/Consecutivo (usa el mismo modelo Serie que ya tienes)
        $table->unsignedBigInteger('serie_id')->nullable()->index();
        $table->string('prefijo', 20)->nullable();
        $table->unsignedBigInteger('numero')->nullable()->index();

        // Cliente
        $table->unsignedBigInteger('socio_negocio_id')->nullable()->index();

        // Fechas y estado
        $table->date('fecha');
        $table->date('vencimiento')->nullable();
        $table->string('estado', 20)->default('borrador'); // borrador|emitida|anulada|cerrado

        // Monetarios
        $table->string('moneda', 3)->default('COP');
        $table->decimal('subtotal', 18, 2)->default(0);
        $table->decimal('impuestos', 18, 2)->default(0);
        $table->decimal('total', 18, 2)->default(0);

        // Pago/condición (para registrar reverso y términos)
        $table->string('tipo_pago', 20)->default('contado'); // contado|credito
        $table->unsignedInteger('plazo_dias')->nullable();
        $table->string('terminos_pago', 255)->nullable();
        $table->unsignedBigInteger('cuenta_cobro_id')->nullable()->index();
        $table->unsignedBigInteger('condicion_pago_id')->nullable()->index();

        // Texto
        $table->string('motivo', 255)->nullable();
        $table->text('notas')->nullable();

        // Totales de aplicación/reversiones
        $table->decimal('aplicado', 18, 2)->default(0);  // cuánto de la NC ya fue aplicado a la factura/pagos
        $table->timestamps();

        // FKs (ajusta nombres de tabla si difiere)
        $table->foreign('factura_id')->references('id')->on('facturas')->nullOnDelete();
        $table->foreign('serie_id')->references('id')->on('series')->nullOnDelete();
        $table->foreign('socio_negocio_id')->references('id')->on('socio_negocios')->nullOnDelete();
        $table->foreign('cuenta_cobro_id')->references('id')->on('plan_cuentas')->nullOnDelete();
        $table->foreign('condicion_pago_id')->references('id')->on('condicion_pagos')->nullOnDelete();
    });
}

};
