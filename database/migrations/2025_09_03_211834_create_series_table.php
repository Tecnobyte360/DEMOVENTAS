<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 80);          // “Factura ventas principal”
            $table->string('prefijo', 10);
            $table->unsignedBigInteger('desde');   // número inicial permitido
            $table->unsignedBigInteger('hasta');   // número final permitido
            $table->unsignedBigInteger('proximo'); // siguiente a asignar

            // Info de resolución (opcional)
            $table->string('resolucion', 120)->nullable();
            $table->date('resolucion_fecha')->nullable();

            // Ventana de vigencia (opcional)
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();

            $table->boolean('activa')->default(true);

            $table->timestamps();

            $table->unique(['prefijo', 'nombre']);
            $table->index(['activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
