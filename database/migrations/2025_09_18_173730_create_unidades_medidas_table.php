<?php

// database/migrations/2025_01_01_000000_create_unidades_medida.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();   
            $table->string('nombre');                 
            $table->string('simbolo', 10)->nullable(); 
            $table->string('tipo', 30)->nullable();  
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('unidades_medida');
    }
};
