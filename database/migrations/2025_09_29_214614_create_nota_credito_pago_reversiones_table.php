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
        Schema::create('nota_credito_pago_reversiones', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('nota_credito_id')->index();
        $table->unsignedBigInteger('pago_factura_id')->index();
        $table->decimal('monto_revertido', 18, 2)->default(0);
        $table->timestamps();

        $table->foreign('nota_credito_id')->references('id')->on('nota_creditos')->cascadeOnDelete();
        $table->foreign('pago_factura_id')->references('id')->on('pagos_facturas')->cascadeOnDelete();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_credito_pago_reversiones');
    }
};
