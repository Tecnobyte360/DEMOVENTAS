<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();

            // Serie y numeración
            $table->unsignedBigInteger('serie_id');
            $table->unsignedBigInteger('numero');      // correlativo dentro de la serie
            $table->string('prefijo', 10);             // denormalizado para visualización/consulta rápida

            // Relaciones comerciales
            $table->unsignedBigInteger('socio_negocio_id');
            $table->unsignedBigInteger('cotizacion_id')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();

            // Fechas y moneda
            $table->date('fecha');
            $table->date('vencimiento')->nullable();
            $table->string('moneda', 8)->default('COP');

            // Pago
            $table->string('tipo_pago', 20)->default('contado'); // contado | credito
            $table->unsignedSmallInteger('plazo_dias')->nullable();

            // Totales
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('impuestos', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('pagado', 14, 2)->default(0);
            $table->decimal('saldo', 14, 2)->default(0);

            // Estado
            $table->string('estado', 30)->default('borrador'); // borrador | emitida | parcialmente_pagada | pagada | anulada

            // Extras
            $table->string('terminos_pago')->nullable();
            $table->text('notas')->nullable();
            $table->string('pdf_path')->nullable();

            $table->timestamps();

            // Índices (con nombres explícitos para portabilidad)
            $table->unique(['serie_id', 'numero'], 'uq_facturas_serie_numero');
            $table->index(['prefijo', 'numero'], 'ix_facturas_prefijo_numero');
            $table->index(['estado', 'vencimiento'], 'ix_facturas_estado_vencimiento');

            // Foreign keys (usa nombres explícitos y ON DELETE según tu lógica)
            $table->foreign('serie_id', 'fk_facturas_series')
                  ->references('id')->on('series')
                  ->onUpdate('cascade');

            $table->foreign('socio_negocio_id', 'fk_facturas_socio')
                  ->references('id')->on('socio_negocios')
                  ->onUpdate('cascade');

            // Nota: si tienes tablas cotizaciones/pedidos, agrega sus FK cuando existan:
            // $table->foreign('cotizacion_id', 'fk_facturas_cotizaciones')
            //       ->references('id')->on('cotizaciones')
            //       ->nullOnDelete()->onUpdate('cascade');
            //
            // $table->foreign('pedido_id', 'fk_facturas_pedidos')
            //       ->references('id')->on('pedidos')
            //       ->nullOnDelete()->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
