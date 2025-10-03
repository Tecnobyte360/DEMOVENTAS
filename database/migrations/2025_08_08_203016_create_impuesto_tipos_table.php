<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('impuesto_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();   // IVA, ICA, RETEFUENTE, etc.
            $table->string('nombre');                 // Nombre legible
            $table->boolean('es_retencion')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedTinyInteger('orden')->default(1);
            $table->timestamps();

            $table->index(['activo','orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impuesto_tipos');
    }
};
