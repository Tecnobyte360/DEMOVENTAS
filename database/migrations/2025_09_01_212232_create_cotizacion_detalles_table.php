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
      Schema::create('cotizacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('bodega_id')->nullable()->constrained('bodegas');
            $table->decimal('cantidad', 15, 3)->default(1);
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->unsignedBigInteger('precio_lista_id')->nullable(); 
            $table->decimal('descuento_pct', 6, 3)->default(0);       
            $table->decimal('impuesto_pct', 6, 3)->default(0);    
            $table->decimal('importe', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_detalles');
    }
};
