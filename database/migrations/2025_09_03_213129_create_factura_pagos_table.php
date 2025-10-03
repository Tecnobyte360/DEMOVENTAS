<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factura_pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_id');

            $table->date('fecha');
            $table->string('metodo', 120)->nullable();
            $table->string('referencia', 120)->nullable();
            $table->decimal('monto', 14, 2);

            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index(['factura_id', 'fecha']);
            $table->foreign('factura_id')->references('id')->on('facturas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_pagos');
    }
};
