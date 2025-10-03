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
       Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_negocio_id')->constrained('socio_negocios');
            $table->date('fecha')->default(now());
            $table->date('vencimiento')->nullable();
            $table->string('lista_precio')->nullable();     
            $table->string('terminos_pago')->nullable();
            $table->string('estado')->default('borrador','enviada','confirmada','cancelada');
            $table->text('notas')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('impuestos', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
