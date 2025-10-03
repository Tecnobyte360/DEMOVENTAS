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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategoria_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('costo', 10, 2);
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0); // Stock global, no por bodega
            $table->integer('stock_minimo')->default(0);
            $table->integer('stock_maximo')->nullable();
            $table->boolean('activo')->default(true); // Activar/desactivar el producto
            $table->boolean('es_articulo_compra')->default(false); // Nuevo campo
            $table->boolean('es_articulo_venta')->default(false); // Nuevo campo
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
