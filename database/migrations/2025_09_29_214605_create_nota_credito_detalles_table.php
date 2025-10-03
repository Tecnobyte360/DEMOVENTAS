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
    Schema::create('nota_credito_detalles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('nota_credito_id')->index();

        // Igual a factura_detalles pero invertido en lógica de negocio
        $table->unsignedBigInteger('producto_id')->nullable()->index();
        $table->unsignedBigInteger('cuenta_ingreso_id')->nullable()->index();
        $table->unsignedBigInteger('bodega_id')->nullable()->index();

        $table->string('descripcion', 255)->nullable();
        $table->decimal('cantidad', 18, 3)->default(1);         
        $table->decimal('precio_unitario', 18, 2)->default(0);
        $table->decimal('descuento_pct', 9, 3)->default(0);
        $table->unsignedBigInteger('impuesto_id')->nullable()->index();
        $table->decimal('impuesto_pct', 9, 3)->default(0);

        $table->timestamps();

        $table->foreign('nota_credito_id')->references('id')->on('nota_creditos')->cascadeOnDelete();
        $table->foreign('producto_id')->references('id')->on('productos')->nullOnDelete();
        $table->foreign('cuenta_ingreso_id')->references('id')->on('plan_cuentas')->nullOnDelete();
        $table->foreign('bodega_id')->references('id')->on('bodegas')->nullOnDelete();
        $table->foreign('impuesto_id')->references('id')->on('impuestos')->nullOnDelete();
    });
}

};
