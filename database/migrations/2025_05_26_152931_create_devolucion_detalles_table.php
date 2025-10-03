<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('devolucion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained()->onDelete('no action');
            $table->foreignId('bodega_id')->constrained()->onDelete('no action');
            $table->integer('cantidad');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('devolucion_detalles');
    }
};
