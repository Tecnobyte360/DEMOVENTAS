<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asientos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('tipo', 20)->default('VENTA');
            $table->string('moneda', 3)->default('COP');
          
            $table->nullableMorphs('origen'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asientos');
    }
};
