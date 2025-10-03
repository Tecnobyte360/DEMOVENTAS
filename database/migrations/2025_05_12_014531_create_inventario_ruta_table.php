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
        Schema::create('inventario_ruta', function (Blueprint $table) {
            $table->id();
             $table->foreignId('ruta_id')->constrained('rutas')->onDelete('cascade');
                $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
                $table->foreignId('bodega_id')->constrained('bodegas')->onDelete('cascade');
                $table->integer('cantidad')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_ruta');
    }
};
