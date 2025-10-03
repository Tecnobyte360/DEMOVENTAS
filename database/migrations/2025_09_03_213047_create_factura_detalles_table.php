<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_id');

            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('bodega_id')->nullable();
            $table->string('descripcion')->nullable();

            $table->decimal('cantidad', 12, 3)->default(0);
            $table->decimal('precio_unitario', 14, 2)->default(0);
            $table->decimal('descuento_pct', 8, 3)->default(0);
            $table->decimal('impuesto_pct', 8, 3)->default(0);

            // calculados
            $table->decimal('importe_base', 14, 2)->default(0);
            $table->decimal('importe_impuesto', 14, 2)->default(0);
            $table->decimal('importe_total', 14, 2)->default(0);

            $table->timestamps();

            $table->index('factura_id');
            $table->foreign('factura_id')->references('id')->on('facturas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};
